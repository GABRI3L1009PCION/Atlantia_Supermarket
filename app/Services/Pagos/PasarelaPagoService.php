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
use App\Services\Pagos\Recurrente\RecurrenteClient;
use App\Services\Pedidos\EstadoPedidoService;
use App\ValueObjects\Dinero;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        private readonly EstadoPedidoService $estadoPedidoService,
        private readonly RecurrenteClient $recurrenteClient
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
            $paymentUuid = (string) Str::uuid();
            $resultado = $this->procesar([
                'pedido' => $pedido,
                'pedido_dto' => $pedidoDTO,
                'payment_uuid' => $paymentUuid,
            ]);

            $payment = Payment::query()->create([
                'uuid' => $paymentUuid,
                'pedido_id' => $pedido->id,
                'metodo' => $metodo,
                'monto' => $pedido->montoACobrar(),
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

        $paymentUuid = (string) ($datos['payment_uuid'] ?? Str::uuid());

        return $this->procesarSegunMetodo($pedido, $pedidoDTO->metodoPago->value, $pedidoDTO, $paymentUuid);
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

            return $this->aplicarEstado($payment, $estado, $transactionId ?: null, ['webhook' => $payload]);
        });
    }

    /**
     * Actualiza el estado de un pago a partir de una pasarela ya verificada.
     *
     * Devuelve el pago actualizado y el estado que tenia antes del cambio, para que
     * quien llama ejecute efectos una sola vez (confirmar pedido, facturar).
     *
     * @param  array<string, mixed>  $payloadExtra
     * @return array{0: Payment, 1: EstadoPago}
     */
    public function actualizarEstadoPasarela(Payment $payment, EstadoPago $estado, array $payloadExtra = []): array
    {
        return DB::transaction(function () use ($payment, $estado, $payloadExtra): array {
            $payment = Payment::query()
                ->with(['pedido.pedidosHijos'])
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $anterior = $payment->estado;

            return [$this->aplicarEstado($payment, $estado, null, $payloadExtra), $anterior];
        });
    }

    /**
     * Aplica una transicion de estado sobre un pago ya bloqueado.
     *
     * @param  array<string, mixed>  $payloadExtra
     */
    private function aplicarEstado(Payment $payment, EstadoPago $estado, ?string $transactionId, array $payloadExtra): Payment
    {
        if (in_array($payment->estado, [EstadoPago::Reembolsado, EstadoPago::Anulado], true)
            || (in_array($payment->estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true) && $estado === EstadoPago::Validando)) {
            return $payment;
        }

        $payloadAnterior = $payment->pasarela_payload ?? [];

        $payment->update([
            'estado' => $estado->value,
            'transaccion_id_pasarela' => $payment->transaccion_id_pasarela ?: $transactionId,
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
                ...$payloadExtra,
            ],
        ]);

        if ($payment->pedido !== null) {
            $this->syncPedidoTreeEstadoPago($payment->pedido, $this->estadoPedidoPago($payment->estado));

            if (in_array($estado, [EstadoPago::Rechazado, EstadoPago::Anulado], true)) {
                $this->stockService->releaseForPedido($payment->pedido);
                $this->cancelPedidoTree($payment->pedido, 'Pedido cancelado por pago rechazado o vencido.');
            }
        }

        return $payment->refresh();
    }

    /**
     * Procesa pago segun metodo.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    private function procesarSegunMetodo(Pedido $pedido, string $metodo, PedidoDTO $pedidoDTO, string $paymentUuid): PagoResultado
    {
        if ($metodo === MetodoPago::Tarjeta->value && RecurrenteClient::habilitado()) {
            $cliente = $pedido->cliente;
            if ($pedidoDTO->usarTarjetaGuardada && filled($cliente?->recurrente_customer_id) && filled($cliente?->recurrente_metodo_pago_id)) {
                return $this->cobrarTarjetaGuardada($pedido, $paymentUuid);
            }

            return $this->crearCheckoutRecurrente($pedido, $paymentUuid);
        }

        return match ($metodo) {
            MetodoPago::Tarjeta->value => new PagoResultado(
                estado: EstadoPago::Pendiente,
                transaccionIdPasarela: null,
                hmacValidado: false,
                validadoAt: null,
                payload: [
                    'gateway' => 'manual_pos',
                    'collection_flow' => 'pos_on_delivery',
                    'amount_due_on_delivery' => $pedido->montoACobrar(),
                    'requires_pos_terminal' => true,
                    'customer_message' => 'El repartidor cobrara con terminal POS al momento de la entrega.',
                ],
            ),
            MetodoPago::Transferencia->value => new PagoResultado(
                estado: EstadoPago::Pendiente,
                referenciaBancaria: $pedidoDTO->referenciaBancaria,
                payload: [
                    'gateway' => 'manual_transfer',
                    'collection_flow' => 'bank_transfer_on_delivery',
                    'amount_due_on_delivery' => $pedido->montoACobrar(),
                    'reference_hint' => $pedidoDTO->referenciaBancaria,
                    'customer_message' => 'La transferencia se confirma al momento de entregar el pedido.',
                ],
            ),
            MetodoPago::Efectivo->value => new PagoResultado(
                estado: EstadoPago::Pendiente,
                payload: [
                    'gateway' => 'cash_on_delivery',
                    'collection_flow' => 'cash_on_delivery',
                    'amount_due_on_delivery' => $pedido->montoACobrar(),
                    'change_requested' => $pedidoDTO->solicitaCambio,
                    'change_requested_for' => $pedidoDTO->cambioPara,
                    'change_required' => $this->calculateChangeRequired($pedido, $pedidoDTO),
                    'customer_message' => $pedidoDTO->solicitaCambio && $pedidoDTO->cambioPara !== null
                        ? 'El cliente solicito cambio para Q '.number_format($pedidoDTO->cambioPara, 2).'.'
                        : 'Cobro en efectivo al entregar.',
                ],
            ),
            default => throw new PagoRechazadoException('Metodo de pago no soportado.'),
        };
    }

    /**
     * Cobro con un toque usando la tarjeta guardada en Recurrente (POST /one_time_payments).
     * Si Recurrente no confirma el cobro, el cliente puede pagar con el checkout normal.
     *
     * @throws PagoRechazadoException
     */
    private function cobrarTarjetaGuardada(Pedido $pedido, string $paymentUuid): PagoResultado
    {
        $totalCents = Dinero::from($pedido->montoACobrar())->toCents();
        if ($totalCents < 500) {
            throw new PagoRechazadoException('El pago con tarjeta en linea requiere un total minimo de Q 5.00.');
        }

        $cobro = $this->recurrenteClient->cobrarTarjetaGuardada(
            (string) $pedido->cliente->recurrente_customer_id,
            (string) $pedido->cliente->recurrente_metodo_pago_id,
            [[
                'name' => 'Pedido Atlantia '.$pedido->numero_pedido,
                'amount_in_cents' => $totalCents,
                'currency' => 'GTQ',
                'quantity' => 1,
            ]],
            'cobro-'.$paymentUuid
        );

        if (($cobro['status'] ?? null) !== 'paid') {
            throw new PagoRechazadoException('Tu banco no aprobó el cobro con la tarjeta guardada. Prueba con otra tarjeta.');
        }

        return new PagoResultado(
            estado: EstadoPago::Pagado,
            transaccionIdPasarela: (string) ($cobro['id'] ?? ''),
            hmacValidado: true,
            validadoAt: now(),
            payload: [
                'gateway' => 'recurrente',
                'collection_flow' => 'saved_card',
                'recurrente_one_time_payment_id' => $cobro['id'] ?? null,
                'customer_message' => 'Pagado con tu tarjeta guardada.',
            ],
        );
    }

    /**
     * Crea el checkout alojado de Recurrente. El pago queda pendiente hasta que
     * el webhook o la verificacion del servidor confirmen el cobro.
     *
     * @throws PagoRechazadoException
     */
    private function crearCheckoutRecurrente(Pedido $pedido, string $paymentUuid): PagoResultado
    {
        $totalCents = Dinero::from($pedido->montoACobrar())->toCents();

        if ($totalCents < 500) {
            throw new PagoRechazadoException('El pago con tarjeta en linea requiere un total minimo de Q 5.00.');
        }

        $expiresAt = now()->addMinutes(max(15, (int) config('services.recurrente.checkout_expires_minutes', 30)));
        $checkout = $this->recurrenteClient->crearCheckout(
            items: [[
                'name' => 'Pedido Atlantia '.$pedido->numero_pedido,
                'amount_in_cents' => $totalCents,
                'currency' => (string) config('services.recurrente.currency', 'GTQ'),
                'quantity' => 1,
            ]],
            successUrl: route('cliente.pagos.recurrente.retorno', ['payment' => $paymentUuid]),
            cancelUrl: route('cliente.pagos.recurrente.cancelado', ['payment' => $paymentUuid]),
            metadata: [
                'payment_uuid' => $paymentUuid,
                'pedido_uuid' => (string) $pedido->uuid,
                'numero_pedido' => (string) $pedido->numero_pedido,
            ],
            expiresAt: $expiresAt,
            idempotencyKey: 'checkout-'.$paymentUuid,
        );

        return new PagoResultado(
            estado: EstadoPago::Pendiente,
            transaccionIdPasarela: (string) $checkout['id'],
            payload: [
                'gateway' => 'recurrente',
                'collection_flow' => 'online_checkout',
                'recurrente_checkout_id' => (string) $checkout['id'],
                'checkout_url' => (string) $checkout['checkout_url'],
                'checkout_expires_at' => $expiresAt->toIso8601String(),
                'amount_in_cents' => $totalCents,
                'live_mode' => (bool) ($checkout['live_mode'] ?? false),
                'customer_message' => 'Completa el pago con tarjeta en la pagina segura de Recurrente.',
            ],
        );
    }

    /**
     * Solicita el reembolso en Recurrente cuando el pago se cobro en linea.
     */
    /**
     * @param  bool  $porPartes  true solo cuando una tienda cancela su parte: el pago sigue acreditado por el resto
     *                           y admite otra devolución hasta completar lo cobrado. Sin él, la primera devolución cierra el pago.
     */
    public function reembolsar(Payment $payment, float $monto, bool $porPartes = false): Payment
    {
        return DB::transaction(function () use ($payment, $monto, $porPartes): Payment {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $importe = Dinero::from($monto);
            /* Un pago puede devolverse por partes (por ejemplo, si cancela solo una de las tiendas del pedido). */
            $yaDevuelto = Dinero::from((float) (($payment->pasarela_payload ?? [])['refund_total'] ?? 0));
            $disponible = Dinero::from($payment->monto)->toCents() - $yaDevuelto->toCents();

            if (! is_finite($monto) || $importe->toCents() <= 0 || $importe->toCents() > $disponible) {
                throw ValidationException::withMessages([
                    'monto_reembolso' => 'El reembolso debe ser mayor a cero y no puede superar lo que queda por devolver del pago.',
                ]);
            }

            if (! in_array($payment->estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
                throw ValidationException::withMessages([
                    'monto_reembolso' => 'Solo se puede reembolsar un pago acreditado que no haya sido reembolsado.',
                ]);
            }
            $payload = $payment->pasarela_payload ?? [];

            if (($payload['gateway'] ?? null) === 'recurrente') {
                $intentId = (string) ($payload['recurrente_intent_id'] ?? '');

                if ($intentId === '') {
                    throw new PagoRechazadoException('El pago en linea no tiene un intent de Recurrente para reembolsar.');
                }

                $esTotal = $yaDevuelto->toCents() === 0 && $importe->toCents() === Dinero::from($payment->monto)->toCents();
                $numero = count((array) ($payload['refunds'] ?? [])) + 1;
                $refund = $this->recurrenteClient->reembolsar(
                    $intentId,
                    $esTotal ? null : $importe->toCents(),
                    $numero === 1 ? 'refund-'.$payment->uuid : 'refund-'.$payment->uuid.'-'.$numero
                );

                if (in_array($refund['status'] ?? null, ['failed', 'voided'], true)) {
                    throw new PagoRechazadoException('Recurrente no pudo procesar el reembolso. Revisa el pago en su panel.');
                }

                $payload['recurrente_refund'] = [
                    'id' => $refund['id'] ?? null,
                    'status' => $refund['status'] ?? null,
                    'failure_reason' => $refund['failure_reason'] ?? null,
                    'customer_refunded_amount_in_cents' => $refund['customer_refunded_amount_in_cents'] ?? null,
                ];
            }

            $total = $yaDevuelto->add($importe);
            $completo = ! $porPartes || $total->toCents() >= Dinero::from($payment->monto)->toCents();

            $payment->update([
                /* Si solo se devolvió una parte, el pago sigue acreditado por el resto. */
                'estado' => $completo ? EstadoPago::Reembolsado->value : $payment->estadoValor(),
                'pasarela_payload' => [
                    ...$payload,
                    'refund_amount' => $importe->toDecimal(),
                    'refund_total' => $total->toDecimal(),
                    'refunds' => [...(array) ($payload['refunds'] ?? []), ['monto' => $importe->toDecimal(), 'at' => now()->toIso8601String()]],
                    'refunded_at' => now()->toIso8601String(),
                ],
            ]);
            if ($completo && $payment->pedido !== null) {
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
            'approved', 'aprobado', 'paid', 'pagado', 'succeeded' => EstadoPago::Aprobado,
            'pending', 'pendiente', 'validando', 'in_review', 'review', 'processing', 'requires_capture' => EstadoPago::Validando,
            'rejected', 'rechazado', 'failed', 'declined', 'canceled', 'cancelled', 'cancelado' => EstadoPago::Rechazado,
            'refunded', 'reembolsado' => EstadoPago::Reembolsado,
            'reversed', 'reversado', 'voided', 'anulado' => EstadoPago::Anulado,
            default => EstadoPago::Validando,
        };
    }

    private function calculateChangeRequired(Pedido $pedido, PedidoDTO $pedidoDTO): float
    {
        if (! $pedidoDTO->solicitaCambio || $pedidoDTO->cambioPara === null) {
            return 0;
        }

        if ($pedidoDTO->cambioPara <= $pedido->montoACobrar()) {
            throw new PagoRechazadoException('La denominacion para cambio debe ser mayor al total del pedido.');
        }

        return round($pedidoDTO->cambioPara - $pedido->montoACobrar(), 2);
    }
}
