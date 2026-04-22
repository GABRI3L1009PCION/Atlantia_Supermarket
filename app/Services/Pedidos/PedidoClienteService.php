<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de pedidos del cliente.
 */
class PedidoClienteService
{
    /**
     * Pagina pedidos propios del cliente.
     */
    public function paginate(User $user): LengthAwarePaginator
    {
        return Pedido::query()
            ->with(['vendor', 'payments'])
            ->where('cliente_id', $user->id)
            ->padres()
            ->latest()
            ->paginate(20);
    }

    /**
     * Detalle de pedido propio.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load([
            'direccion',
            'items.producto.vendor',
            'pedidosHijos.vendor',
            'pedidosHijos.items.producto',
            'pedidosHijos.dteFacturas',
            'payments',
            'estados.usuario',
            'deliveryRoute',
        ]);
    }
}
