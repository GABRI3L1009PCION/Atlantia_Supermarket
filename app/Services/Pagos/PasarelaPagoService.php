<?php

namespace App\Services\Pagos;

use App\Contracts\PasarelaPagoContract;
use App\DTOs\PagoResultado;
use App\DTOs\PedidoDTO;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Exceptions\PagoRechazadoException;
use App\Models\Payment;
use App\Models\Pedido;
use App\Services\Inventario\StockService;
use App\Services\Pedidos\EstadoPedidoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Servicio de pagos con contrato intercambiable de pasarela.
 */
class PasarelaPagoService implements PasarelaPagoContract
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly StockService $stockService,
        private readonly EstadoPedidoService $estadoPedidoService
    ) {}

    /**
     * Registra el pago del checkout.
     *
     *
     * @throws PagoRechazadoException
     */
    public function registrarPagoCheckout(Pedido $pedido, PedidoDTO $pedidoDTO): Payment
    {
        return DB::transaction(function () use ($pedido, $pedidoDTO): Payment {
            $metodo = $pedidoDTO->metodoPago->value;
            $resultado = $this->procesar([
                'pedido' => $pedido,
                'pedido_dto' => $pedidoDTO,
            ]);

            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(),
                'pedido_id' => $pedido->id,
                'metodo' => $metodo,
                'monto' => $pedido->total,
                'estado' => $resultado->estado->value,
                'transaccion_id_pasarela' => $resultado->transaccionIdPasarela,
                'hmac_validado' => $resultado->hmacValidado,
                'referencia_bancaria' => $resultado->referenciaBancaria,
                'validado_por' => null,
                'validado_at' => $resultado->validadoAt,
                'pasarela_payload' => $resultado->toArray(),
            ]);

            $this->syncPedidoTreeEstadoPago($pedido, $this->estadoPedidoPago($payment->estado));

            return $payment;
        });
    }

    /**
     * Procesa un pago segun el contrato intercambiable.
     *
     * @param  array<string, mixed>  $datos
     */
    public function procesar(array $datos): PagoResultado
    {
        $pedido = $datos['pedido'] ?? null;
        $pedidoDTO = $datos['pedido_dto'] ?? null;

        if (! $pedido instanceof Pedido || ! $pedidoDTO instanceof PedidoDTO) {
            throw new PagoRechazadoException('No se recibio el contexto requerido para procesar el pago.');
        }

        return $this->procesarSegunMetodo($pedido, $pedidoDTO->metodoPago->value, $pedidoDTO);
    }

    /**
     * Registra confirmacion desde webhook de pasarela.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function confirmarDesdeWebhook(array $payload, array $headers): ?Payment
    {
        $paymentUuid = (string) ($payload['payment_uuid'] ?? '');
        $transactionId = (string) (
            $payload['transaction_id']
            ?? $payload['transaccion_id_pasarela']
            ?? $payload['payment_intent']
            ?? data_get($payload, 'payload.transaction_id')
            ?? data_get($payload, 'payload.payment_intent')
            ?? data_get($payload, 'payload.data.object.id')
            ?? ''
        );

        if ($paymentUuid === '' && $transactionId === '') {
            return null;
        }

        return DB::transaction(function () use ($payload, $paymentUuid, $transactionId): ?Payment {
            $payment = Payment::query()
                ->with(['pedido.pedidosHijos'])
                ->where(function ($query) use ($paymentUuid, $transactionId): void {
                    if ($paymentUuid !== '') {
                        $query->where('uuid', $paymentUuid);
                    }

                    if ($transactionId !== '') {
                        $query->orWhere('transaccion_id_pasarela', $transactionId);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

            $estado = $this->estadoDesdeWebhook($payload);
            $payloadAnterior = $payment->pasarela_payload ?? [];

            $payment->update([
                'estado' => $estado->value,
                'transaccion_id_pasarela' => $payment->transaccion_id_pasarela ?: ($transactionId ?: null),
                'hmac_validado' => true,
                'validado_at' => in_array($estado, [
                    EstadoPago::Aprobado,
                    EstadoPago::Pagado,
                    EstadoPago::Rechazado,
                    EstadoPago::Anulado,
                    EstadoPago::Reembolsado,
                ], true) ? now() : $payment->validado_at,
                'pasarela_payload' => [
                    ...$payloadAnterior,
                    'webhook' => $payload,
                ],
            ]);
            if ($payment->pedido !== null) {
                $this->syncPedidoTreeEstadoPago($payment->pedido, $this->estadoPedidoPago($payment->estado));

                if (in_array($estado, [EstadoPago::Rechazado, EstadoPago::Anulado], true)) {
                    $this->stockService->releaseForPedido($payment->pedido);
                    $this->cancelPedidoTree($payment->pedido, 'Pedido cancelado por webhook de pago rechazado.');
                }
            }

            return $payment->refresh();
        });
    }

    /**
     * Procesa pago segun metodo.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarSegunMetodo(Pedido $pedido, string $metodo, PedidoDTO $pedidoDTO): PagoResultado
    {
        return match ($metodo) {
            MetodoPago::Tarjeta->value => $this->procesarTarjetaStripe($pedido, $pedidoDTO),
            MetodoPago::Transferencia->value => new PagoResultado(
                estado: EstadoPago::Validando,
                referenciaBancaria: $pedidoDTO->referenciaBancaria,
            ),
            MetodoPago::Efectivo->value => new PagoResultado(estado: EstadoPago::Pendiente),
            default => throw new PagoRechazadoException('Metodo de pago no soportado.'),
        };
    }

    /**
     * Procesa tarjeta con Stripe Payment Intents.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarTarjetaStripe(Pedido $pedido, PedidoDTO $pedidoDTO): PagoResultado
    {
        $secret = (string) config('services.stripe.secret_key');

        if ($secret === '') {
            throw new PagoRechazadoException('Stripe no esta configurado para procesar tarjetas.');
        }

        $stripeCredential = (string) ($pedidoDTO->cardToken ?? '');

        if ($stripeCredential === '') {
            throw new PagoRechazadoException('No se recibio el metodo de pago seguro de Stripe.');
        }

        $usesConfirmationToken = ! str_starts_with($stripeCredential, 'pm_');
        $payload = [
            'amount' => (int) round(((float) $pedido->total) * 100),
            'currency' => strtolower((string) config('services.stripe.currency', 'gtq')),
            'confirm' => 'true',
            'description' => 'Atlantia Supermarket pedido '.$pedido->numero_pedido,
            'metadata[pedido_uuid]' => $pedido->uuid,
            'metadata[numero_pedido]' => $pedido->numero_pedido,
        ];

        if ($usesConfirmationToken) {
            $payload['confirmation_token'] = $stripeCredential;
            $payload['automatic_payment_methods[enabled]'] = 'true';
        } else {
            $payload['payment_method'] = $stripeCredential;
            $payload['payment_method_types[]'] = 'card';
        }

        $response = Http::asForm()
            ->withToken($secret)
            ->withHeaders(['Idempotency-Key' => 'pedido-'.$pedido->uuid])
            ->timeout(20)
            ->post('https://api.stripe.com/v1/payment_intents', $payload);

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

        return new PagoResultado(
            estado: EstadoPago::Aprobado,
            transaccionIdPasarela: $payload['id'] ?? null,
            hmacValidado: true,
            validadoAt: now(),
            payload: [
                'gateway' => 'stripe',
                'authorization' => $status,
                'amount' => (float) $pedido->total,
                'currency' => 'GTQ',
                'stripe_payment_intent' => $payload['id'] ?? null,
                'stripe_status' => $status,
            ],
        );
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
            if ($payment->pedido !== null) {
                $this->syncPedidoTreeEstadoPago($payment->pedido, EstadoPago::Reembolsado);
            }

            return $payment->refresh();
        });
    }

    /**
     * Sincroniza el estado de pago del pedido padre y sus pedidos por vendedor.
     */
    private function syncPedidoTreeEstadoPago(Pedido $pedido, EstadoPago $estado): void
    {
        $pedido->update(['estado_pago' => $estado->value]);
        $pedido->pedidosHijos()->update(['estado_pago' => $estado->value]);
    }

    /**
     * Cancela el pedido padre y sus hijos sin liberar stock mas de una vez.
     */
    private function cancelPedidoTree(Pedido $pedido, string $nota): void
    {
        $pedido->loadMissing('pedidosHijos');
        $pedidos = collect([$pedido])->merge($pedido->pedidosHijos);

        foreach ($pedidos as $pedidoTreeItem) {
            if (in_array($pedidoTreeItem->estadoValor(), [
                EstadoPedido::Cancelado->value,
                EstadoPedido::Rechazado->value,
                EstadoPedido::Entregado->value,
            ], true)) {
                continue;
            }

            $this->estadoPedidoService->registrar($pedidoTreeItem, EstadoPedido::Cancelado, $nota);
        }
    }

    /**
     * Mapea estado de payment a estado_pago del pedido.
     *
     * @param  string  $estado
     * @return string
     */
    private function estadoPedidoPago(string|EstadoPago $estado): EstadoPago
    {
        $estadoValue = $estado instanceof EstadoPago ? $estado->value : $estado;

        return match ($estadoValue) {
            EstadoPago::Aprobado->value => EstadoPago::Pagado,
            EstadoPago::Pagado->value => EstadoPago::Pagado,
            EstadoPago::Validando->value => EstadoPago::Validando,
            EstadoPago::Rechazado->value => EstadoPago::Rechazado,
            EstadoPago::Anulado->value => EstadoPago::Anulado,
            EstadoPago::Reembolsado->value => EstadoPago::Reembolsado,
            default => EstadoPago::Pendiente,
        };
    }

    /**
     * Normaliza estados de pasarelas externas al enum interno.
     *
     * @param  array<string, mixed>  $payload
     */
    private function estadoDesdeWebhook(array $payload): EstadoPago
    {
        $status = Str::of((string) (
            $payload['status']
            ?? $payload['estado']
            ?? data_get($payload, 'payload.status')
            ?? data_get($payload, 'payload.data.object.status')
            ?? ''
        ))
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        return match ($status) {
            'approved', 'aprobado', 'paid', 'pagado', 'succeeded', 'processing', 'requires_capture' => EstadoPago::Aprobado,
            'pending', 'pendiente', 'validando', 'in_review', 'review' => EstadoPago::Validando,
            'rejected', 'rechazado', 'failed', 'declined', 'canceled', 'cancelled', 'cancelado' => EstadoPago::Rechazado,
            'refunded', 'reembolsado' => EstadoPago::Reembolsado,
            'reversed', 'reversado', 'voided', 'anulado' => EstadoPago::Anulado,
            default => EstadoPago::Validando,
        };
    }
}
