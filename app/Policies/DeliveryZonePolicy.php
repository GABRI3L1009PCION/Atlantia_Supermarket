<?php

namespace App\Policies;

use App\Models\DeliveryZone;
use App\Models\User;

/**
 * Politica para zonas de entrega globales.
 */
class DeliveryZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, DeliveryZone $zona): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, DeliveryZone $zona): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, DeliveryZone $zona): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
