<?php

namespace App\Policies;

use App\Models\Empleado;
use App\Models\User;

/**
 * Politica para empleados internos.
 */
class EmpleadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, Empleado $empleado): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, Empleado $empleado): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, Empleado $empleado): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
