<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
}

