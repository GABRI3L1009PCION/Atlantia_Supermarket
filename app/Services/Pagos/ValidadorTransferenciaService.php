<?php

namespace App\Services\Pagos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Inventario\StockService;
use App\Services\Pedidos\EstadoPedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Servicio de validacion manual de transferencias.
 */
class ValidadorTransferenciaService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly StockService $stockService,
        private readonly EstadoPedidoService $estadoPedidoService
    ) {}

    /**
     * Lista transferencias pendientes de validacion por el equipo interno.
     *
     * @param  array<string, mixed>  $filters
     */
    public function pending(array $filters = []): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['pedido', 'pedido.cliente'])
            ->where('metodo', MetodoPago::Transferencia->value)
            ->whereIn('estado', [EstadoPago::Pendiente->value, EstadoPago::Validando->value])
            ->when($filters['fecha_desde'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filters['fecha_hasta'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->latest()
            ->paginate(20);
    }

    /**
     * Valida o rechaza una transferencia bancaria.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws TransaccionFallidaException
     */
    public function validar(Payment $payment, array $data, User $empleado): Payment
    {
        try {
            return DB::transaction(function () use ($payment, $data, $empleado): Payment {
                $payment = Payment::query()
                    ->with(['pedido.pedidosHijos'])
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($payment->metodoValor() !== MetodoPago::Transferencia->value) {
                    throw new TransaccionFallidaException('El pago no corresponde a una transferencia.');
                }

                if (! in_array($payment->estadoValor(), [EstadoPago::Pendiente->value, EstadoPago::Validando->value], true)) {
                    throw new TransaccionFallidaException('La transferencia ya fue procesada.');
                }

                $estado = ($data['estado'] ?? null) === EstadoPago::Aprobado->value
                    ? EstadoPago::Aprobado
                    : EstadoPago::Rechazado;
                $pedidoEstadoPago = $estado === EstadoPago::Aprobado ? EstadoPago::Pagado : EstadoPago::Rechazado;

                $payment->update([
                    'estado' => $estado->value,
                    'referencia_bancaria' => $data['referencia_bancaria'] ?? $payment->referencia_bancaria,
                    'validado_por' => $empleado->id,
                    'validado_at' => now(),
                    'pasarela_payload' => [
                        ...($payment->pasarela_payload ?? []),
                        'tipo' => 'transferencia_manual',
                        'observaciones' => $data['notas'] ?? $data['observaciones'] ?? null,
                    ],
                ]);

                if ($payment->pedido !== null) {
                    $this->syncPedidoTreeEstadoPago($payment->pedido, $pedidoEstadoPago);

                    if ($estado === EstadoPago::Rechazado) {
                        $this->stockService->releaseForPedido($payment->pedido);
                        $this->cancelPedidoTree($payment->pedido, $empleado, 'Pedido cancelado por transferencia rechazada.');
                    }
                }

                return $payment->refresh();
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible validar la transferencia.', previous: $exception);
        }
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
     * Cancela el pedido padre y sus pedidos hijos cuando el pago fue rechazado.
     */
    private function cancelPedidoTree(Pedido $pedido, User $empleado, string $nota): void
    {
        $pedido->loadMissing('pedidosHijos');

        foreach (collect([$pedido])->merge($pedido->pedidosHijos) as $pedidoTreeItem) {
            if (in_array($pedidoTreeItem->estadoValor(), [
                EstadoPedido::Cancelado->value,
                EstadoPedido::Rechazado->value,
                EstadoPedido::Entregado->value,
            ], true)) {
                continue;
            }

            $this->estadoPedidoService->registrar($pedidoTreeItem, EstadoPedido::Cancelado, $nota, $empleado);
        }
    }
}
