<?php

namespace App\Services\Auth;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Servicio de matriz RBAC.
 */
class RolPermisoService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly PermissionRegistrar $permissionRegistrar)
    {
    }

    /**
     * Devuelve matriz de roles y permisos.
     *
     * @param array<string, mixed> $filters
     * @return array<string, Collection<int, mixed>>
     */
    public function matrix(array $filters = []): array
    {
        return [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Crea un rol operativo y asigna permisos iniciales.
     *
     * @param array<string, mixed> $data
     * @return Role
     */
    public function createRole(array $data): Role
    {
        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        $this->permissionRegistrar->forgetCachedPermissions();

        return $role->load('permissions');
    }

    /**
     * Crea un permiso personalizado para nuevos modulos escalables.
     *
     * @param array<string, mixed> $data
     * @return Permission
     */
    public function createPermission(array $data): Permission
    {
        $permission = Permission::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $this->permissionRegistrar->forgetCachedPermissions();

        return $permission;
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
        $this->permissionRegistrar->forgetCachedPermissions();
    }

    /**
     * Elimina un rol operativo que no este protegido.
     */
    public function deleteRole(Role $role): bool
    {
        if (in_array($role->name, $this->protectedRoles(), true)) {
            return false;
        }

        $role->delete();
        $this->permissionRegistrar->forgetCachedPermissions();

        return true;
    }

    /**
     * Roles base protegidos contra eliminacion accidental.
     *
     * @return array<int, string>
     */
    private function protectedRoles(): array
    {
        return [
            'super_admin',
            'admin',
            'cliente',
            'vendedor',
            'repartidor',
            'empleado',
        ];
    }
}
