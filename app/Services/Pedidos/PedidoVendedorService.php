<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de pedidos recibidos por vendedor.
 */
class PedidoVendedorService
{
    /**
     * Pagina pedidos del vendedor autenticado.
     */
    public function paginate(User $user): LengthAwarePaginator
    {
        return Pedido::query()
            ->with(['cliente', 'direccion'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->paginate(25);
    }

    /**
     * Detalle de pedido recibido.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load(['cliente', 'direccion', 'items.producto', 'estados.usuario', 'payments']);
    }

    /**
     * Actualiza estado del pedido del vendedor.
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

        if ($pedido->estado === EstadoPedido::ListoParaEntrega) {
            app(NotificadorPedidoService::class)->pedidoListoParaRecoger($pedido);
        }

        return $pedido->refresh();
    }
}
