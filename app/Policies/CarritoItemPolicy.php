<?php

namespace App\Policies;

use App\Models\CarritoItem;
use App\Models\User;

/**
 * Politica de autorizacion para items del carrito.
 */
class CarritoItemPolicy
{
    /**
     * Determina si el usuario puede actualizar el item.
     */
    public function update(User $user, CarritoItem $item): bool
    {
        return (int) $item->carrito?->user_id === (int) $user->id
            && $user->hasRole('cliente');
    }

    /**
     * Determina si el usuario puede eliminar el item.
     */
    public function delete(User $user, CarritoItem $item): bool
    {
        return $this->update($user, $item);
    }
}

