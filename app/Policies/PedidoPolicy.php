<?php

namespace App\Policies;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;

/**
 * Politica de autorizacion para pedidos.
 */
class PedidoPolicy
{
    /**
     * Permite acceso global a administradores.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar pedidos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('empleado') || $user->can('view orders');
    }

    /**
     * Determina si el usuario puede ver un pedido.
     */
    public function view(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsCliente($user, $pedido)
            || $this->ownsPedidoAsVendor($user, $pedido)
            || $this->isAssignedCourier($user, $pedido)
            || $user->hasRole('empleado')
            || $user->can('view orders');
    }

    /**
     * Determina si el cliente puede crear pedidos.
     */
    public function create(User $user): bool
    {
        return $user->status === 'active'
            && ($user->hasRole('cliente') || $user->can('create orders'));
    }

    /**
     * Determina si el usuario puede actualizar un pedido.
     */
    public function update(User $user, Pedido $pedido): bool
    {
        return $user->hasRole('empleado')
            || $this->updateVendorStatus($user, $pedido)
            || $this->updateDeliveryStatus($user, $pedido);
    }

    /**
     * Determina si el usuario puede eliminar logicamente un pedido.
     */
    public function delete(User $user, Pedido $pedido): bool
    {
        return $user->can('delete orders');
    }

    /**
     * Determina si el cliente puede iniciar checkout.
     */
    public function checkout(User $user): bool
    {
        return $user->status === 'active'
            && ($user->hasRole('cliente') || $user->can('checkout'));
    }

    /**
     * Determina si el cliente puede listar sus pedidos.
     */
    public function viewOwnOrders(User $user): bool
    {
        return $user->hasRole('cliente') || $user->can('view own orders');
    }

    /**
     * Determina si el vendedor puede listar pedidos de su tienda.
     */
    public function viewOwnVendorOrders(User $user): bool
    {
        return $user->vendor !== null
            && ($user->hasRole('vendedor') || $user->can('view vendor orders'));
    }

    /**
     * Determina si el vendedor puede ver un pedido recibido.
     */
    public function viewVendorOrder(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsVendor($user, $pedido)
            && ($user->hasRole('vendedor') || $user->can('view vendor orders'));
    }

    /**
     * Determina si el vendedor puede actualizar estado operativo.
     */
    public function updateVendorStatus(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsVendor($user, $pedido)
            && ! in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Entregado], true)
            && ($user->hasRole('vendedor') || $user->can('update vendor order status'));
    }

    /**
     * Determina si el repartidor puede listar pedidos asignados.
     */
    public function viewAssignedOrders(User $user): bool
    {
        return $user->hasRole('repartidor') || $user->can('view assigned orders');
    }

    /**
     * Determina si el repartidor puede ver un pedido asignado.
     */
    public function viewAssigned(User $user, Pedido $pedido): bool
    {
        return $this->isAssignedCourier($user, $pedido)
            && ($user->hasRole('repartidor') || $user->can('view assigned orders'));
    }

    /**
     * Determina si el repartidor puede actualizar estado de entrega.
     */
    public function updateDeliveryStatus(User $user, Pedido $pedido): bool
    {
        return $this->isAssignedCourier($user, $pedido)
            && ! in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Entregado], true)
            && ($user->hasRole('repartidor') || $user->can('update delivery status'));
    }

    /**
     * Determina si el repartidor puede reportar una incidencia.
     */
    public function reportIncident(User $user, Pedido $pedido): bool
    {
        return $this->isAssignedCourier($user, $pedido)
            && ! in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Entregado], true)
            && ($user->hasRole('repartidor') || $user->can('update delivery status'));
    }

    /**
     * Determina si el usuario puede rastrear un pedido.
     */
    public function track(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsCliente($user, $pedido)
            || $this->isAssignedCourier($user, $pedido)
            || $this->ownsPedidoAsVendor($user, $pedido)
            || $user->hasRole('empleado')
            || $user->can('track orders');
    }

    /**
     * Determina si el cliente puede crear resena desde el pedido.
     */
    public function review(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsCliente($user, $pedido)
            && $pedido->estado === EstadoPedido::Entregado
            && $user->status === 'active';
    }

    /**
     * Determina si el usuario puede cancelar un pedido.
     */
    public function cancel(User $user, Pedido $pedido): bool
    {
        if (in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Entregado], true)) {
            return false;
        }

        return $this->ownsPedidoAsCliente($user, $pedido)
            || $this->ownsPedidoAsVendor($user, $pedido)
            || $user->hasRole('empleado')
            || $user->can('cancel orders');
    }

    /**
     * Verifica ownership del cliente.
     */
    private function ownsPedidoAsCliente(User $user, Pedido $pedido): bool
    {
        return (int) $pedido->cliente_id === (int) $user->id;
    }

    /**
     * Verifica ownership del vendedor.
     */
    private function ownsPedidoAsVendor(User $user, Pedido $pedido): bool
    {
        return $user->vendor !== null
            && $pedido->vendor_id !== null
            && (int) $pedido->vendor_id === (int) $user->vendor->id;
    }

    /**
     * Verifica que el pedido este asignado al repartidor.
     */
    private function isAssignedCourier(User $user, Pedido $pedido): bool
    {
        $pedido->loadMissing('deliveryRoute');

        return $pedido->deliveryRoute !== null
            && (int) $pedido->deliveryRoute->repartidor_id === (int) $user->id;
    }
}
