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

        $permissionsByRole = [
            'cliente' => [
                'catalogo.ver',
                'carrito.gestionar',
                'checkout.crear',
                'pedidos.ver_propios',
                'resenas.crear',
                'direcciones.gestionar',
            ],
            'vendedor' => [
                'vendedor.panel',
                'productos.gestionar_propios',
                'inventario.gestionar_propios',
                'pedidos.ver_recibidos',
                'fel.ver_propios',
                'comisiones.ver_propias',
                'ml.ver_predicciones_propias',
            ],
            'admin' => [
                'admin.panel',
                'usuarios.gestionar',
                'roles.gestionar',
                'vendedores.aprobar',
                'vendedores.suspender',
                'categorias.gestionar',
                'productos.moderar',
                'pedidos.ver_todos',
                'pagos.ver_todos',
                'fel.ver_todos',
                'delivery_zones.gestionar',
                'ml.monitorear',
                'auditoria.ver',
                'configuracion.gestionar',
            ],
            'repartidor' => [
                'repartidor.panel',
                'entregas.ver_asignadas',
                'entregas.actualizar_estado',
                'tracking.enviar_ubicacion',
            ],
            'empleado' => [
                'empleado.panel',
                'transferencias.validar',
                'contact_messages.atender',
                'resenas.moderar',
                'fraud_alerts.revisar',
            ],
        ];

        foreach (array_unique(array_merge(...array_values($permissionsByRole))) as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach ($permissionsByRole as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
