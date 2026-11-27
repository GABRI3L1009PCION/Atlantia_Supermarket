<?php

namespace App\Services\Pagos\Recurrente;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Clientes\MembresiaService;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Pagos\PasarelaPagoService;
use App\Services\Pedidos\CheckoutService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquesta los pagos en linea de Recurrente: webhooks, verificacion al regresar
 * del checkout y vencimiento de checkouts no pagados.
 */
class RecurrentePagoService
{
    /** Minutos de gracia despues del vencimiento antes de cancelar el pedido. */
    private const GRACIA_VENCIMIENTO_MINUTOS = 10;

    public function __construct(
        private readonly RecurrenteClient $client,
        private readonly PasarelaPagoService $pasarelaPagoService,
        private readonly CheckoutService $checkoutService
    ) {}

    /**
     * Procesa un evento de webhook ya verificado.
     *
     * @param  array<string, mixed>  $evento
     */
    public function procesarEvento(array $evento): ?Payment
    {
        $tipo = (string) ($evento['event_type'] ?? $evento['type'] ?? '');
        $payment = $this->paymentDesdeEvento($evento);

        if ($payment === null && in_array($tipo, ['intent.succeeded', 'payment_intent.succeeded'], true) && $this->renovarMembresia($evento)) {
            return null;
        }

        if ($payment === null) {
            Log::info('Webhook de Recurrente sin pago asociado en Atlantia.', [
                'event_type' => $tipo,
                'intent_id' => $evento['id'] ?? null,
                'checkout_id' => data_get($evento, 'checkout.id'),
            ]);

            return null;
        }

        return match ($tipo) {
            'intent.succeeded', 'payment_intent.succeeded' => $this->registrarCobro(
                $payment,
                (string) ($evento['id'] ?? ''),
                isset($evento['amount_in_cents']) ? (int) $evento['amount_in_cents'] : null,
                isset($evento['currency']) ? (string) $evento['currency'] : null,
                'webhook'
            ),
            'intent.failed', 'payment_intent.failed', 'intent.canceled', 'payment_intent.canceled' => $this->registrarIntentoFallido(
                $payment,
                (string) ($evento['id'] ?? ''),
                (string) (data_get($evento, 'details.failure_reason') ?? $evento['failure_reason'] ?? $tipo)
            ),
            default => $payment,
        };
    }

    /**
     * Cobro mensual de la membresia: extiende un mes la cuenta duena de la suscripcion.
     * El id de la suscripcion se busca en los campos que documenta Recurrente para pagos de suscripcion.
     *
     * @param  array<string, mixed>  $evento
     */
    private function renovarMembresia(array $evento): bool
    {
        $suscripcionId = collect([
            data_get($evento, 'subscription.id'),
            data_get($evento, 'subscription_id'),
            data_get($evento, 'payment.paymentable.type') === 'Subscription' ? data_get($evento, 'payment.paymentable.id') : null,
            data_get($evento, 'paymentable.type') === 'Subscription' ? data_get($evento, 'paymentable.id') : null,
        ])->first(fn (mixed $valor): bool => is_string($valor) && $valor !== '');

        if ($suscripcionId === null) {
            return false;
        }

        $vendor = Vendor::query()->where('plan_suscripcion_id', $suscripcionId)->first();
        if ($vendor !== null) {
            app(PlanesVendedorService::class)->aplicar($vendor, (string) $vendor->plan, $suscripcionId);
            Log::info('Plan de vendedor renovado por cobro de suscripcion.', ['vendor_id' => $vendor->id]);

            return true;
        }

        $user = User::query()->where('membresia_suscripcion_id', $suscripcionId)->first();
        if ($user === null) {
            return false;
        }

        app(MembresiaService::class)->extenderUnMes($user, $suscripcionId);
        Log::info('Membresia renovada por cobro de suscripcion.', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Consulta el checkout en Recurrente y aplica su estado. Se usa al volver del
     * checkout y para reconciliar webhooks perdidos.
     */
    public function sincronizar(Payment $payment): Payment
    {
        $payload = $payment->pasarela_payload ?? [];
        $checkoutId = (string) ($payload['recurrente_checkout_id'] ?? '');

        if (($payload['gateway'] ?? null) !== 'recurrente' || $checkoutId === '' || ! CheckoutService::esperaPagoEnLinea($payment)) {
            return $payment;
        }

        $checkout = $this->client->obtenerCheckout($checkoutId);
        $status = (string) ($checkout['status'] ?? '');

        if ($status === 'paid') {
            return $this->registrarCobro(
                $payment,
                (string) (data_get($checkout, 'latest_intent.id') ?? data_get($checkout, 'payment.id') ?? ''),
                isset($checkout['total_in_cents']) ? (int) $checkout['total_in_cents'] : null,
                isset($checkout['currency']) ? (string) $checkout['currency'] : null,
                'api'
            );
        }

        $venceEn = isset($payload['checkout_expires_at']) ? Carbon::parse($payload['checkout_expires_at']) : null;
        $vencido = $status === 'expired'
            || ($status === 'unpaid' && $venceEn !== null && now()->greaterThan($venceEn->copy()->addMinutes(self::GRACIA_VENCIMIENTO_MINUTOS)));

        if ($vencido) {
            [$payment] = $this->pasarelaPagoService->actualizarEstadoPasarela($payment, EstadoPago::Rechazado, [
                'recurrente_checkout_status' => $status,
                'recurrente_vencido_at' => now()->toIso8601String(),
            ]);
        }

        return $payment;
    }

    /**
     * Revisa pagos en linea pendientes cuyo checkout ya debio vencer.
     *
     * @return array{revisados: int, pagados: int, vencidos: int, errores: int}
     */
    public function reconciliarPendientes(): array
    {
        $resumen = ['revisados' => 0, 'pagados' => 0, 'vencidos' => 0, 'errores' => 0];

        Payment::query()
            ->where('metodo', MetodoPago::Tarjeta->value)
            ->whereIn('estado', [EstadoPago::Pendiente->value, EstadoPago::Validando->value])
            ->where('pasarela_payload->gateway', 'recurrente')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->each(function (Payment $payment) use (&$resumen): void {
                $resumen['revisados']++;

                try {
                    $actualizado = $this->sincronizar($payment);
                } catch (Throwable $exception) {
                    $resumen['errores']++;
                    Log::warning('No fue posible reconciliar un pago de Recurrente.', [
                        'payment_id' => $payment->id,
                        'error' => $exception->getMessage(),
                    ]);

                    return;
                }

                match ($actualizado->estado) {
                    EstadoPago::Aprobado, EstadoPago::Pagado => $resumen['pagados']++,
                    EstadoPago::Rechazado => $resumen['vencidos']++,
                    default => null,
                };
            });

        return $resumen;
    }

    /**
     * Acredita un cobro confirmado, validando monto y moneda.
     */
    private function registrarCobro(Payment $payment, string $intentId, ?int $montoCents, ?string $moneda, string $origen): Payment
    {
        $payload = $payment->pasarela_payload ?? [];
        $esperadoCents = (int) ($payload['amount_in_cents'] ?? 0);
        $monedaEsperada = strtoupper((string) config('services.recurrente.currency', 'GTQ'));
        $datos = array_filter([
            'recurrente_intent_id' => str_starts_with($intentId, 'in_') || empty($payload['recurrente_intent_id']) ? ($intentId ?: null) : null,
            'recurrente_cobro_origen' => $origen,
            'recurrente_cobrado_at' => now()->toIso8601String(),
        ]);

        if (($montoCents !== null && $montoCents !== $esperadoCents) || ($moneda !== null && strtoupper($moneda) !== $monedaEsperada)) {
            Log::error('Recurrente reporto un cobro con monto o moneda distintos al pedido.', [
                'payment_id' => $payment->id,
                'esperado_cents' => $esperadoCents,
                'recibido_cents' => $montoCents,
                'moneda' => $moneda,
            ]);

            [$payment] = $this->pasarelaPagoService->actualizarEstadoPasarela($payment, EstadoPago::Validando, [
                ...$datos,
                'revision_manual' => 'Monto o moneda del cobro no coinciden con el pedido.',
            ]);

            return $payment;
        }

        if (in_array($payment->estado, [EstadoPago::Rechazado, EstadoPago::Anulado], true)) {
            /* El cliente pago despues de que el pedido se cancelo por vencimiento. */
            Log::critical('Cobro de Recurrente recibido para un pedido ya cancelado. Requiere reembolso manual.', [
                'payment_id' => $payment->id,
                'intent_id' => $intentId,
            ]);

            $payment->update([
                'pasarela_payload' => [
                    ...$payload,
                    ...$datos,
                    'revision_manual' => 'Cobro recibido despues de cancelar el pedido. Reembolsar en Recurrente.',
                ],
            ]);

            return $payment->refresh();
        }

        [$payment, $anterior] = $this->pasarelaPagoService->actualizarEstadoPasarela($payment, EstadoPago::Aprobado, $datos);

        if (! in_array($anterior, [EstadoPago::Aprobado, EstadoPago::Pagado], true) && $payment->estado === EstadoPago::Aprobado) {
            $this->checkoutService->confirmarPagoEnLinea($payment);
        }

        return $payment->refresh();
    }

    /**
     * Un intento fallido no cancela el pedido: el cliente puede reintentar en el
     * mismo checkout mientras no venza.
     */
    private function registrarIntentoFallido(Payment $payment, string $intentId, string $motivo): Payment
    {
        if (! CheckoutService::esperaPagoEnLinea($payment)) {
            return $payment;
        }

        [$payment] = $this->pasarelaPagoService->actualizarEstadoPasarela($payment, EstadoPago::Pendiente, [
            'recurrente_ultimo_fallo' => [
                'intent_id' => $intentId ?: null,
                'motivo' => $motivo,
                'at' => now()->toIso8601String(),
            ],
        ]);

        return $payment;
    }

    /**
     * Ubica el pago por id de checkout o, en su defecto, por metadata.
     *
     * @param  array<string, mixed>  $evento
     */
    private function paymentDesdeEvento(array $evento): ?Payment
    {
        $checkoutId = (string) (data_get($evento, 'checkout.id') ?? '');
        $paymentUuid = (string) (data_get($evento, 'checkout.metadata.payment_uuid') ?? data_get($evento, 'metadata.payment_uuid') ?? '');

        if ($checkoutId !== '') {
            $payment = Payment::query()
                ->where('transaccion_id_pasarela', $checkoutId)
                ->where('pasarela_payload->gateway', 'recurrente')
                ->first();

            if ($payment !== null) {
                return $payment;
            }
        }

        if ($paymentUuid !== '') {
            return Payment::query()
                ->where('uuid', $paymentUuid)
                ->where('pasarela_payload->gateway', 'recurrente')
                ->first();
        }

        return null;
    }
}
