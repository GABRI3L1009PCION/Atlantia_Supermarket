<?php

namespace App\Services\Pagos;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Exceptions\PagoRechazadoException;
use App\Models\Payment;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Servicio de pagos con contrato intercambiable de pasarela.
 */
class PasarelaPagoService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly VerificadorHmacService $verificadorHmacService)
    {
    }

    /**
     * Registra el pago del checkout.
     *
     * @param Pedido $pedido
     * @param array<string, mixed> $data
     * @return Payment
     *
     * @throws PagoRechazadoException
     */
    public function registrarPagoCheckout(Pedido $pedido, array $data): Payment
    {
        return DB::transaction(function () use ($pedido, $data): Payment {
            $metodo = $data['metodo_pago'] instanceof MetodoPago
                ? $data['metodo_pago']->value
                : (string) $data['metodo_pago'];
            $payload = $this->procesarSegunMetodo($pedido, $metodo, $data);

            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(),
                'pedido_id' => $pedido->id,
                'metodo' => $metodo,
                'monto' => $pedido->total,
                'estado' => $payload['estado'] instanceof EstadoPago ? $payload['estado']->value : $payload['estado'],
                'transaccion_id_pasarela' => $payload['transaccion_id_pasarela'] ?? null,
                'hmac_validado' => $payload['hmac_validado'] ?? false,
                'referencia_bancaria' => $payload['referencia_bancaria'] ?? null,
                'validado_por' => null,
                'validado_at' => $payload['validado_at'] ?? null,
                'pasarela_payload' => $payload,
            ]);

            $pedido->update(['estado_pago' => $this->estadoPedidoPago($payment->estado)->value]);

            return $payment;
        });
    }

    /**
     * Registra confirmacion desde webhook de pasarela.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return Payment|null
     */
    public function confirmarDesdeWebhook(array $payload, array $headers): ?Payment
    {
        $secret = (string) config('services.payment_gateway.webhook_secret', '');
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = (string) ($headers['x-atlantia-signature'][0] ?? $headers['X-Atlantia-Signature'][0] ?? '');

        if (! $this->verificadorHmacService->verify($body, $signature, $secret)) {
            return null;
        }

        return DB::transaction(function () use ($payload): ?Payment {
            $payment = Payment::query()
                ->where('transaccion_id_pasarela', $payload['transaction_id'] ?? null)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

            $payment->update([
                'estado' => ($payload['status'] ?? null) === 'approved'
                    ? EstadoPago::Aprobado->value
                    : EstadoPago::Rechazado->value,
                'hmac_validado' => true,
                'validado_at' => now(),
                'pasarela_payload' => $payload,
            ]);
            $payment->pedido()->update(['estado_pago' => $this->estadoPedidoPago($payment->estado)->value]);

            return $payment->refresh();
        });
    }

    /**
     * Procesa pago segun metodo.
     *
     * @param Pedido $pedido
     * @param string $metodo
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarSegunMetodo(Pedido $pedido, string $metodo, array $data): array
    {
        return match ($metodo) {
            MetodoPago::Tarjeta->value => $this->procesarTarjetaStripe($pedido, $data),
            MetodoPago::Transferencia->value => [
                'estado' => EstadoPago::Validando,
                'referencia_bancaria' => $data['referencia_bancaria'] ?? null,
            ],
            MetodoPago::Efectivo->value => ['estado' => EstadoPago::Pendiente],
            default => throw new PagoRechazadoException('Metodo de pago no soportado.'),
        };
    }

    /**
     * Procesa tarjeta con Stripe Payment Intents.
     *
     * @param Pedido $pedido
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarTarjetaStripe(Pedido $pedido, array $data): array
    {
        $secret = (string) config('services.stripe.secret_key');

        if ($secret === '') {
            throw new PagoRechazadoException('Stripe no esta configurado para procesar tarjetas.');
        }

        $paymentMethod = (string) ($data['card_token'] ?? '');

        if ($paymentMethod === '') {
            throw new PagoRechazadoException('No se recibio el metodo de pago seguro de Stripe.');
        }

        $response = Http::asForm()
            ->withToken($secret)
            ->withHeaders(['Idempotency-Key' => 'pedido-' . $pedido->uuid])
            ->timeout(20)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round(((float) $pedido->total) * 100),
                'currency' => strtolower((string) config('services.stripe.currency', 'gtq')),
                'payment_method' => $paymentMethod,
                'confirm' => 'true',
                'description' => 'Atlantia Supermarket pedido ' . $pedido->numero_pedido,
                'metadata[pedido_uuid]' => $pedido->uuid,
                'metadata[numero_pedido]' => $pedido->numero_pedido,
            ]);

        if (! $response->successful()) {
            throw new PagoRechazadoException(
                (string) ($response->json('error.message') ?: 'Stripe rechazo el pago.')
            );
        }

        $payload = $response->json();
        $status = (string) ($payload['status'] ?? '');

        if (! in_array($status, ['succeeded', 'processing', 'requires_capture'], true)) {
            throw new PagoRechazadoException('Stripe no aprobo el pago de la tarjeta.');
        }

        return [
            'estado' => EstadoPago::Aprobado,
            'transaccion_id_pasarela' => $payload['id'] ?? null,
            'hmac_validado' => true,
            'validado_at' => now(),
            'gateway' => 'stripe',
            'authorization' => $status,
            'amount' => (float) $pedido->total,
            'currency' => 'GTQ',
            'stripe_payment_intent' => $payload['id'] ?? null,
            'stripe_status' => $status,
        ];
    }

    /**
     * Solicita reembolso en Stripe cuando existe PaymentIntent.
     */
    public function reembolsar(Payment $payment, float $monto): Payment
    {
        return DB::transaction(function () use ($payment, $monto): Payment {
            $payment->refresh();
            $payload = $payment->pasarela_payload ?? [];

            if ($payment->metodo === MetodoPago::Tarjeta && ($payload['stripe_payment_intent'] ?? null)) {
                $secret = (string) config('services.stripe.secret_key');

                if ($secret === '') {
                    throw new PagoRechazadoException('Stripe no esta configurado para reembolsos.');
                }

                $response = Http::asForm()
                    ->withToken($secret)
                    ->timeout(20)
                    ->post('https://api.stripe.com/v1/refunds', [
                        'payment_intent' => $payload['stripe_payment_intent'],
                        'amount' => (int) round($monto * 100),
                    ]);

                if (! $response->successful()) {
                    throw new PagoRechazadoException(
                        (string) ($response->json('error.message') ?: 'Stripe rechazo el reembolso.')
                    );
                }

                $payload['stripe_refund'] = $response->json();
            }

            $payment->update([
                'estado' => EstadoPago::Reembolsado->value,
                'pasarela_payload' => $payload,
            ]);
            $payment->pedido()->update(['estado_pago' => EstadoPago::Reembolsado->value]);

            return $payment->refresh();
        });
    }

    /**
     * Mapea estado de payment a estado_pago del pedido.
     *
     * @param string $estado
     * @return string
     */
    private function estadoPedidoPago(string|EstadoPago $estado): EstadoPago
    {
        $estadoValue = $estado instanceof EstadoPago ? $estado->value : $estado;

        return match ($estadoValue) {
            EstadoPago::Aprobado->value => EstadoPago::Pagado,
            EstadoPago::Validando->value => EstadoPago::Validando,
            EstadoPago::Rechazado->value => EstadoPago::Rechazado,
            EstadoPago::Reembolsado->value => EstadoPago::Reembolsado,
            default => EstadoPago::Pendiente,
        };
    }
}
