<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Politica para administracion de roles y permisos.
 */
class RolePolicy
{
    /**
     * Permite ver la matriz de roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.gestionar');
    }

    /**
     * Permite ver un rol.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.gestionar');
    }

    /**
     * Permite crear roles operativos.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.gestionar');
    }

    /**
     * Permite editar permisos de roles operativos.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.gestionar') && $role->name !== 'super_admin';
    }

    /**
     * Permite eliminar solo roles personalizados.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.gestionar')
            && ! in_array($role->name, ['super_admin', 'admin', 'cliente', 'vendedor', 'repartidor', 'empleado'], true);
    }
}
