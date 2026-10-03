<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder de roles y permisos base del sistema.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de RBAC.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionsByRole = config('rbac.permissions_by_role', []);
        $catalogPermissions = config('rbac.catalog_permissions', []);

        foreach (array_unique(array_merge($catalogPermissions, ...array_values($permissionsByRole))) as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach ($permissionsByRole as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($roleName === 'super_admin') {
                $role->syncPermissions(Permission::query()->pluck('name')->all());

                continue;
            }

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
