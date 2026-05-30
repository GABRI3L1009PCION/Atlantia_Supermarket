<?php

namespace App\Policies;

use App\Models\Nomina;
use App\Models\User;

/**
 * Acceso administrativo a planillas internas.
 */
class NominaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, Nomina $nomina): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, Nomina $nomina): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
