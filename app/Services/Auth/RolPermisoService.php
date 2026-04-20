<?php

namespace App\Services\Auth;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Servicio de matriz RBAC.
 */
class RolPermisoService
{
    /**
     * Devuelve matriz de roles y permisos.
     *
     * @param array<string, mixed> $filters
     * @return array<string, Collection<int, mixed>>
     */
    public function matrix(array $filters = []): array
    {
        return [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Sincroniza permisos de un rol operativo.
     *
     * @param array<string, mixed> $data
     */
    public function syncPermissions(Role $role, array $data): void
    {
        if ($role->name === 'super_admin') {
            return;
        }

        $role->syncPermissions($data['permissions'] ?? []);
    }
}

