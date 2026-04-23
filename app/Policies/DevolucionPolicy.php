<?php

namespace App\Policies;

use App\Enums\EstadoPedido;
use App\Models\Devolucion;
use App\Models\Pedido;
use App\Models\User;

/**
 * Politica para solicitudes de devolucion.
 */
class DevolucionPolicy
{
    /**
     * Admin y super admin tienen acceso administrativo.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determina si puede ver una devolucion.
     */
    public function view(User $user, Devolucion $devolucion): bool
    {
        return $user->isAdministrator() || (int) $devolucion->user_id === (int) $user->id;
    }

    /**
     * Determina si el cliente puede solicitar devolucion del pedido.
     */
    public function create(User $user, Pedido $pedido): bool
    {
        return (int) $pedido->cliente_id === (int) $user->id
            && $pedido->estado === EstadoPedido::Entregado
            && $pedido->updated_at->greaterThanOrEqualTo(now()->subDays(7));
    }

    /**
     * Determina si admin puede resolver devoluciones.
     */
    public function update(User $user, Devolucion $devolucion): bool
    {
        return $user->isAdministrator() && $devolucion->estado === 'solicitada';
    }

    /**
     * El borrado no se permite por trazabilidad.
     */
    public function delete(User $user, Devolucion $devolucion): bool
    {
        return false;
    }
}
