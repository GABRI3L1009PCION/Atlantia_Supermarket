<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Events\RepartidorAsignado;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\User;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio administrativo de pedidos.
 */
class PedidoAdminService
{
    /**
     * Pagina pedidos globales.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Pedido::query()
            ->with(['cliente', 'vendor', 'deliveryRoute.repartidor'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('numero_pedido', 'like', '%' . $search . '%')
                        ->orWhere('uuid', 'like', '%' . $search . '%')
                        ->orWhereHas('cliente', function ($clienteQuery) use ($search): void {
                            $clienteQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('vendor', function ($vendorQuery) use ($search): void {
                            $vendorQuery->where('business_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filters['metodo_pago'] ?? null, fn ($query, $metodo) => $query->where('metodo_pago', $metodo))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Detalle de pedido.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load([
            'cliente',
            'vendor',
            'direccion',
            'items.producto',
            'payments.splits.vendor',
            'estados.usuario',
            'deliveryRoute.repartidor',
            'dteFacturas',
        ]);
    }

    /**
     * Actualiza el flujo operativo del pedido.
     *
     * @param array<string, mixed> $data
     */
    public function update(Pedido $pedido, array $data, User $usuario): Pedido
    {
        return DB::transaction(function () use ($pedido, $data, $usuario): Pedido {
            $estadoAnterior = $pedido->estado;
            $estadoPagoAnterior = $pedido->estado_pago;

            $pedido->fill([
                'estado' => $data['estado'],
                'estado_pago' => $data['estado_pago'],
                'notas' => $data['notas'] ?? $pedido->notas,
            ]);

            if ($data['estado'] === EstadoPedido::Confirmado->value && $pedido->confirmado_at === null) {
                $pedido->confirmado_at = now();
            }

            if ($data['estado'] === EstadoPedido::Cancelado->value) {
                $pedido->cancelado_at = now();
            }

            $pedido->save();

            if ($estadoAnterior !== $pedido->estado || ! empty($data['notas_historial'])) {
                PedidoEstado::query()->create([
                    'pedido_id' => $pedido->id,
                    'estado' => $pedido->estadoValor(),
                    'notas' => $data['notas_historial'] ?? $data['notas'] ?? 'Actualizacion administrativa.',
                    'usuario_id' => $usuario->id,
                ]);

                if ($pedido->estado === EstadoPedido::ListoParaEntrega) {
                    app(NotificadorPedidoService::class)->pedidoListoParaRecoger($pedido);
                }
            }

            $payment = $pedido->payments()->latest()->first();

            if ($payment !== null && $estadoPagoAnterior !== $pedido->estado_pago) {
                $payment->update([
                    'estado' => match ($pedido->estado_pago) {
                        EstadoPago::Pagado => EstadoPago::Aprobado->value,
                        default => $pedido->estadoPagoValor(),
                    },
                ]);
            }

            if (array_key_exists('repartidor_id', $data)) {
                if ($data['repartidor_id'] === null && $pedido->deliveryRoute !== null) {
                    $pedido->deliveryRoute->update([
                        'repartidor_id' => null,
                        'estado' => 'pendiente',
                    ]);
                }

                if (! empty($data['repartidor_id'])) {
                    $repartidorAnterior = $pedido->deliveryRoute?->repartidor_id;

                    DeliveryRoute::query()->updateOrCreate(
                        ['pedido_id' => $pedido->id],
                        [
                            'uuid' => $pedido->deliveryRoute?->uuid ?? (string) Str::uuid(),
                            'repartidor_id' => $data['repartidor_id'],
                            'estado' => in_array($pedido->estado, [EstadoPedido::EnRuta, EstadoPedido::Entregado], true)
                                ? 'iniciada'
                                : 'asignada',
                            'asignada_at' => $pedido->deliveryRoute?->asignada_at ?? now(),
                            'aceptada_at' => $repartidorAnterior === (int) $data['repartidor_id']
                                ? $pedido->deliveryRoute?->aceptada_at
                                : null,
                        ]
                    );

                    if ($repartidorAnterior !== (int) $data['repartidor_id']) {
                        RepartidorAsignado::dispatch($pedido->fresh(), User::query()->findOrFail($data['repartidor_id']));
                    }
                }
            }

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Actualiza pedidos por lote.
     *
     * @param array<int, string> $uuids
     */
    public function updateBatch(array $uuids, array $data, User $usuario): int
    {
        $pedidos = Pedido::query()
            ->whereIn('uuid', $uuids)
            ->get();

        foreach ($pedidos as $pedido) {
            $this->update($pedido, $data, $usuario);
        }

        return $pedidos->count();
    }
}
