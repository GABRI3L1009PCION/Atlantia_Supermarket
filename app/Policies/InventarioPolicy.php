<?php

namespace App\Policies;

use App\Models\User;

/**
 * Politica de inventario.
 */
class InventarioPolicy
{
    public function viewOwnInventory(User $user): bool
    {
        return $user->hasRole('vendedor') && $user->vendor !== null;
    }
}

