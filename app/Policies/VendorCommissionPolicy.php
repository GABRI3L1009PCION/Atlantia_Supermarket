<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorCommission;

/**
 * Politica de comisiones de vendedores.
 */
class VendorCommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function viewOwnCommissions(User $user): bool
    {
        return $user->hasRole('vendedor') && $user->vendor !== null;
    }

    public function view(User $user, VendorCommission $commission): bool
    {
        return $user->isAdministrator() || (int) $commission->vendor_id === (int) $user->vendor?->id;
    }

    public function update(User $user, VendorCommission $commission): bool
    {
        return $user->isAdministrator();
    }
}
