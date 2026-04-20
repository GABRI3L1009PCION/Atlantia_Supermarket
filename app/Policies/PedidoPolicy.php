<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

/**
 * Politica de autorizacion para pedidos.
 */
class PedidoPolicy
{
    /**
     * Permite acceso global a administradores.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar pedidos.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('empleado') || $user->can('view orders');
    }

    /**
     * Determina si el usuario puede ver un pedido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
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
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->status === 'active'
            && ($user->hasRole('cliente') || $user->can('create orders'));
    }

    /**
     * Determina si el usuario puede actualizar un pedido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function update(User $user, Pedido $pedido): bool
    {
        return $user->hasRole('empleado')
            || $this->updateVendorStatus($user, $pedido)
            || $this->updateDeliveryStatus($user, $pedido);
    }

    /**
     * Determina si el usuario puede eliminar logicamente un pedido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function delete(User $user, Pedido $pedido): bool
    {
        return $user->can('delete orders');
    }

    /**
     * Determina si el cliente puede iniciar checkout.
     *
     * @param User $user
     * @return bool
     */
    public function checkout(User $user): bool
    {
        return $user->status === 'active'
            && ($user->hasRole('cliente') || $user->can('checkout'));
    }

    /**
     * Determina si el cliente puede listar sus pedidos.
     *
     * @param User $user
     * @return bool
     */
    public function viewOwnOrders(User $user): bool
    {
        return $user->hasRole('cliente') || $user->can('view own orders');
    }

    /**
     * Determina si el vendedor puede listar pedidos de su tienda.
     *
     * @param User $user
     * @return bool
     */
    public function viewOwnVendorOrders(User $user): bool
    {
        return $user->vendor !== null
            && ($user->hasRole('vendedor') || $user->can('view vendor orders'));
    }

    /**
     * Determina si el vendedor puede ver un pedido recibido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function viewVendorOrder(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsVendor($user, $pedido)
            && ($user->hasRole('vendedor') || $user->can('view vendor orders'));
    }

    /**
     * Determina si el vendedor puede actualizar estado operativo.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function updateVendorStatus(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsVendor($user, $pedido)
            && ! in_array($pedido->estado, ['cancelado', 'entregado'], true)
            && ($user->hasRole('vendedor') || $user->can('update vendor order status'));
    }

    /**
     * Determina si el repartidor puede listar pedidos asignados.
     *
     * @param User $user
     * @return bool
     */
    public function viewAssignedOrders(User $user): bool
    {
        return $user->hasRole('repartidor') || $user->can('view assigned orders');
    }

    /**
     * Determina si el repartidor puede ver un pedido asignado.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function viewAssigned(User $user, Pedido $pedido): bool
    {
        return $this->isAssignedCourier($user, $pedido)
            && ($user->hasRole('repartidor') || $user->can('view assigned orders'));
    }

    /**
     * Determina si el repartidor puede actualizar estado de entrega.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function updateDeliveryStatus(User $user, Pedido $pedido): bool
    {
        return $this->isAssignedCourier($user, $pedido)
            && ! in_array($pedido->estado, ['cancelado', 'entregado'], true)
            && ($user->hasRole('repartidor') || $user->can('update delivery status'));
    }

    /**
     * Determina si el usuario puede rastrear un pedido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
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
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function review(User $user, Pedido $pedido): bool
    {
        return $this->ownsPedidoAsCliente($user, $pedido)
            && $pedido->estado === 'entregado'
            && $user->status === 'active';
    }

    /**
     * Determina si el usuario puede cancelar un pedido.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    public function cancel(User $user, Pedido $pedido): bool
    {
        if (in_array($pedido->estado, ['cancelado', 'entregado'], true)) {
            return false;
        }

        return $this->ownsPedidoAsCliente($user, $pedido)
            || $this->ownsPedidoAsVendor($user, $pedido)
            || $user->hasRole('empleado')
            || $user->can('cancel orders');
    }

    /**
     * Verifica ownership del cliente.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    private function ownsPedidoAsCliente(User $user, Pedido $pedido): bool
    {
        return (int) $pedido->cliente_id === (int) $user->id;
    }

    /**
     * Verifica ownership del vendedor.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    private function ownsPedidoAsVendor(User $user, Pedido $pedido): bool
    {
        return $user->vendor !== null
            && $pedido->vendor_id !== null
            && (int) $pedido->vendor_id === (int) $user->vendor->id;
    }

    /**
     * Verifica que el pedido este asignado al repartidor.
     *
     * @param User $user
     * @param Pedido $pedido
     * @return bool
     */
    private function isAssignedCourier(User $user, Pedido $pedido): bool
    {
        $pedido->loadMissing('deliveryRoute');

        return $pedido->deliveryRoute !== null
            && (int) $pedido->deliveryRoute->repartidor_id === (int) $user->id;
    }
}
