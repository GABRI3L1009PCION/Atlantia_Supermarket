<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            ->with(['cliente', 'vendor'])
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
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
}

