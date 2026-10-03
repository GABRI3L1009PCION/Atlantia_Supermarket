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
    public function __construct(private readonly PermissionRegistrar $permissionRegistrar) {}

    /**
     * Devuelve matriz de roles y permisos.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, Collection<int, mixed>>
     */
    public function matrix(array $filters = []): array
    {
        $this->ensurePermissionCatalog();

        return [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Crea un rol operativo y asigna permisos iniciales.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role
    {
        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->sanitizePermissionsForRole($role->name, $data['permissions'] ?? []));
        $this->permissionRegistrar->forgetCachedPermissions();

        return $role->load('permissions');
    }

    /**
     * Crea un permiso personalizado para nuevos modulos escalables.
     *
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
     */
    public function syncPermissions(Role $role, array $data): void
    {
        if ($role->name === 'super_admin') {
            return;
        }

        $role->syncPermissions($this->sanitizePermissionsForRole($role->name, $data['permissions'] ?? []));
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
        return config('rbac.protected_roles', []);
    }

    /**
     * Garantiza que los permisos documentados por la UI existan en Spatie.
     */
    private function ensurePermissionCatalog(): void
    {
        foreach ($this->permissionCatalog() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
    }

    /**
     * Catalogo base mostrado en la vista de gestion granular.
     *
     * @return array<int, string>
     */
    private function permissionCatalog(): array
    {
        return config('rbac.catalog_permissions', []);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function sanitizePermissionsForRole(string $roleName, array $permissions): array
    {
        $catalog = collect($this->permissionCatalog());
        $restrictions = collect(config('rbac.restricted_permissions', []));
        $normalized = collect($permissions)
            ->filter(fn ($permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->intersect($catalog);

        if ($roleName === 'admin') {
            $normalized->push('admin.panel');
        }

        if ($normalized->contains('carrito.gestionar')) {
            $normalized->push('carrito.crear');
        }

        $normalized = $normalized->unique()->values();

        return $normalized
            ->reject(function (string $permission) use ($roleName, $restrictions): bool {
                $allowedRoles = $restrictions->get($permission);

                return is_array($allowedRoles) && ! in_array($roleName, $allowedRoles, true);
            })
            ->values()
            ->all();
    }
}
