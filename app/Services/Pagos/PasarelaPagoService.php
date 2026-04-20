<?php

namespace App\Services\Pagos;

use App\Exceptions\PagoRechazadoException;
use App\Models\Payment;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
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
            $metodo = (string) $data['metodo_pago'];
            $payload = $this->procesarSegunMetodo($pedido, $metodo, $data);

            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(),
                'pedido_id' => $pedido->id,
                'metodo' => $metodo,
                'monto' => $pedido->total,
                'estado' => $payload['estado'],
                'transaccion_id_pasarela' => $payload['transaccion_id_pasarela'] ?? null,
                'hmac_validado' => $payload['hmac_validado'] ?? false,
                'referencia_bancaria' => $payload['referencia_bancaria'] ?? null,
                'validado_por' => null,
                'validado_at' => $payload['validado_at'] ?? null,
                'pasarela_payload' => $payload,
            ]);

            $pedido->update(['estado_pago' => $this->estadoPedidoPago($payment->estado)]);

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
                'estado' => $payload['status'] === 'approved' ? 'aprobado' : 'rechazado',
                'hmac_validado' => true,
                'validado_at' => now(),
                'pasarela_payload' => $payload,
            ]);
            $payment->pedido()->update(['estado_pago' => $this->estadoPedidoPago($payment->estado)]);

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
            'tarjeta' => $this->procesarTarjetaMock($pedido, $data),
            'transferencia' => ['estado' => 'validando', 'referencia_bancaria' => $data['referencia_bancaria'] ?? null],
            'efectivo' => ['estado' => 'pendiente'],
            default => throw new PagoRechazadoException('Metodo de pago no soportado.'),
        };
    }

    /**
     * Procesa tarjeta contra contrato mock de pasarela.
     *
     * @param Pedido $pedido
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarTarjetaMock(Pedido $pedido, array $data): array
    {
        if (($data['card_token'] ?? null) === 'tok_rechazada') {
            throw new PagoRechazadoException('La pasarela rechazo el pago.');
        }

        return [
            'estado' => 'aprobado',
            'transaccion_id_pasarela' => 'ATL-PAY-' . Str::upper(Str::random(12)),
            'hmac_validado' => true,
            'validado_at' => now(),
            'gateway' => 'mock-visanet',
            'authorization' => 'APROBADA',
            'amount' => (float) $pedido->total,
            'currency' => 'GTQ',
        ];
    }

    /**
     * Mapea estado de payment a estado_pago del pedido.
     *
     * @param string $estado
     * @return string
     */
    private function estadoPedidoPago(string $estado): string
    {
        return match ($estado) {
            'aprobado' => 'pagado',
            'validando' => 'validando',
            'rechazado' => 'rechazado',
            'reembolsado' => 'reembolsado',
            default => 'pendiente',
        };
    }
}
