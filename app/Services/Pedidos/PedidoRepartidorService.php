<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de pedidos asignados al repartidor.
 */
class PedidoRepartidorService
{
    /**
     * Lista pedidos asignados.
     *
     * @return Collection<int, Pedido>
     */
    public function assigned(User $user): Collection
    {
        return Pedido::query()
            ->with(['direccion', 'deliveryRoute'])
            ->whereHas('deliveryRoute', fn ($query) => $query->where('repartidor_id', $user->id))
            ->latest()
            ->get();
    }

    /**
     * Detalle de pedido asignado.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load(['direccion', 'items.producto', 'deliveryRoute', 'cliente']);
    }

    /**
     * Actualiza estado de entrega.
     *
     * @param array<string, mixed> $data
     */
    public function updateEstado(Pedido $pedido, array $data, User $user): Pedido
    {
        $pedido->update(['estado' => $data['estado']]);
        $pedido->estados()->create([
            'estado' => $data['estado'],
            'notas' => $data['notas'] ?? null,
            'usuario_id' => $user->id,
        ]);

        return $pedido->refresh();
    }

    /**
     * Acepta una entrega asignada al repartidor.
     */
    public function accept(Pedido $pedido, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $user): Pedido {
            $pedido->loadMissing('deliveryRoute');

            if ($pedido->deliveryRoute !== null && $pedido->deliveryRoute->aceptada_at === null) {
                $pedido->deliveryRoute->update(['aceptada_at' => now()]);
            }

            if ($pedido->estado === EstadoPedido::Confirmado) {
                $pedido->update(['estado' => EstadoPedido::EnPreparacion->value]);
            }

            $pedido->estados()->create([
                'estado' => $pedido->estadoValor(),
                'notas' => 'Entrega aceptada por el repartidor. Se notificara cuando el pedido este listo.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Marca el pedido como recogido e inicia la ruta hacia el cliente.
     */
    public function pickup(Pedido $pedido, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $user): Pedido {
            $pedido->loadMissing('deliveryRoute');

            $pedido->update(['estado' => EstadoPedido::EnRuta->value]);

            if ($pedido->deliveryRoute !== null) {
                $pedido->deliveryRoute->update([
                    'estado' => 'iniciada',
                    'aceptada_at' => $pedido->deliveryRoute->aceptada_at ?? now(),
                    'iniciada_at' => $pedido->deliveryRoute->iniciada_at ?? now(),
                ]);
            }

            $pedido->estados()->create([
                'estado' => EstadoPedido::EnRuta->value,
                'notas' => 'Pedido recogido por el repartidor y en camino al cliente.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Completa la entrega con evidencia opcional.
     *
     * @param array<string, mixed> $data
     */
    public function deliver(Pedido $pedido, array $data, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $data, $user): Pedido {
            $pedido->loadMissing('deliveryRoute');

            $evidencePath = null;

            if (($data['foto_entrega'] ?? null) !== null) {
                $evidencePath = $data['foto_entrega']->store('entregas', 'public');
            }

            $pedido->update(['estado' => EstadoPedido::Entregado->value]);

            if ($pedido->deliveryRoute !== null) {
                $startedAt = $pedido->deliveryRoute->iniciada_at ?? $pedido->deliveryRoute->asignada_at ?? now();

                $pedido->deliveryRoute->update([
                    'estado' => 'completada',
                    'completada_at' => now(),
                    'tiempo_real_min' => max(1, (int) $startedAt->diffInMinutes(now())),
                    'foto_entrega_path' => $evidencePath ?? $pedido->deliveryRoute->foto_entrega_path,
                ]);
            }

            $pedido->estados()->create([
                'estado' => EstadoPedido::Entregado->value,
                'notas' => $data['notas'] ?? 'Pedido entregado al cliente.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }
}
