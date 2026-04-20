<?php

namespace App\Policies;

use App\Models\DeliveryRoute;
use App\Models\User;

/**
 * Politica de rutas de entrega.
 */
class DeliveryRoutePolicy
{
    public function viewAssignedRoutes(User $user): bool
    {
        return $user->hasRole('repartidor');
    }

    public function view(User $user, DeliveryRoute $route): bool
    {
        return (int) $route->repartidor_id === (int) $user->id;
    }

    public function complete(User $user, DeliveryRoute $route): bool
    {
        return $this->view($user, $route) && $route->estado !== 'completada';
    }
}

