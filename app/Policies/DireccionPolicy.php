<?php

namespace App\Policies;

use App\Models\Cliente\Direccion;
use App\Models\User;

/**
 * Politica de direcciones del cliente.
 */
class DireccionPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('cliente');
    }

    public function update(User $user, Direccion $direccion): bool
    {
        return (int) $direccion->user_id === (int) $user->id;
    }

    public function delete(User $user, Direccion $direccion): bool
    {
        return $this->update($user, $direccion);
    }
}

