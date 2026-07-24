<?php

namespace App\Services\Auditoria;

use App\Models\AuditLog;
use App\Models\CourierSupportTicket;
use App\Models\DeliveryZone;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductionReadinessAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function rbacAudit(): array
    {
        $expectedRoles = collect(config('rbac.permissions_by_role', []));
        $catalogPermissions = collect(config('rbac.catalog_permissions', []))->unique()->values();
        $restrictedPermissions = collect(config('rbac.restricted_permissions', []));

        $roles = Role::query()->with('permissions')->orderBy('name')->get()->keyBy('name');
        $actualPermissions = Permission::query()->pluck('name')->sort()->values();

        $missingRoles = $expectedRoles->keys()->diff($roles->keys())->values()->all();
        $unexpectedRoles = $roles->keys()->diff($expectedRoles->keys())->values()->all();
        $missingPermissions = $catalogPermissions->diff($actualPermissions)->values()->all();
        $unexpectedPermissions = $actualPermissions->diff($catalogPermissions)->values()->all();

        $roleMismatches = $expectedRoles->map(function (array $permissions, string $roleName) use ($roles): array {
            $role = $roles->get($roleName);

            if ($role === null) {
                return [
                    'missing' => $permissions,
                    'unexpected' => [],
                ];
            }

            $actual = $role->permissions->pluck('name')->sort()->values();
            $expected = collect($permissions)->sort()->values();

            return [
                'missing' => $expected->diff($actual)->values()->all(),
                'unexpected' => $roleName === 'super_admin'
                    ? []
                    : $actual->diff($expected)->values()->all(),
            ];
        })->filter(fn (array $diff): bool => $diff['missing'] !== [] || $diff['unexpected'] !== []);

        $restrictedViolations = $restrictedPermissions
            ->map(function (array $allowedRoles, string $permission): array {
                $holders = Role::query()
                    ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
                    ->pluck('name')
                    ->values();

                $violations = $holders->reject(fn (string $roleName): bool => in_array($roleName, $allowedRoles, true))->values();

                return [
                    'permission' => $permission,
                    'allowed_roles' => $allowedRoles,
                    'violating_roles' => $violations->all(),
                ];
            })
            ->filter(fn (array $row): bool => $row['violating_roles'] !== [])
            ->values();

        $moduleSummary = $catalogPermissions
            ->groupBy(fn (string $permission): string => explode('.', $permission)[0] ?? 'general')
            ->map(fn (Collection $permissions, string $module): array => [
                'module' => $module,
                'permission_count' => $permissions->count(),
            ])
            ->sortBy('module')
            ->values()
            ->all();

        $status = $missingRoles !== [] || $missingPermissions !== [] || $restrictedViolations->isNotEmpty()
            ? 'error'
            : ($unexpectedRoles !== [] || $unexpectedPermissions !== [] || $roleMismatches->isNotEmpty() ? 'warn' : 'ok');

        return [
            'status' => $status,
            'missing_roles' => $missingRoles,
            'unexpected_roles' => $unexpectedRoles,
            'missing_permissions' => $missingPermissions,
            'unexpected_permissions' => $unexpectedPermissions,
            'role_mismatches' => $roleMismatches->all(),
            'restricted_permission_violations' => $restrictedViolations->all(),
            'module_summary' => $moduleSummary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function demoDataAudit(): array
    {
        $users = User::query()->get(['id', 'name', 'email', 'phone']);
        $vendors = Vendor::query()->get(['id', 'business_name', 'email_publico', 'telefono_publico', 'descripcion']);
        $products = Producto::query()->get(['id', 'nombre', 'sku', 'descripcion']);
        $orders = Pedido::query()->get(['id', 'numero_pedido', 'notas']);
        $supportTickets = CourierSupportTicket::query()->get(['id', 'message']);

        $findings = [
            'users' => $this->suspiciousRows($users, ['name', 'email', 'phone']),
            'vendors' => $this->suspiciousRows($vendors, ['business_name', 'email_publico', 'telefono_publico', 'descripcion']),
            'products' => $this->suspiciousRows($products, ['nombre', 'sku', 'descripcion']),
            'orders' => $this->suspiciousRows($orders, ['numero_pedido', 'notas']),
            'support_tickets' => $this->suspiciousRows($supportTickets, ['message']),
        ];

        $totals = collect($findings)->map(fn (array $rows): int => count($rows));
        $status = $totals->sum() > 0 ? 'error' : 'ok';

        return [
            'status' => $status,
            'development_seeders_enabled' => app()->environment('local'),
            'suspicious_totals' => $totals->all(),
            'samples' => collect($findings)->map(
                fn (array $rows): array => array_slice($rows, 0, 5)
            )->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function operationalReadiness(): array
    {
        $approvedVendors = Vendor::query()->where('status', 'approved')->count();
        $activeProducts = Producto::query()->where('is_active', true)->where('visible_catalogo', true)->count();
        $inventoryCoverage = Producto::query()
            ->where('is_active', true)
            ->whereDoesntHave('inventario')
            ->count();
        $activeZones = DeliveryZone::query()->where('activa', true)->count();
        $adminUsers = User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))->count();
        $supportUsers = User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['soporte', 'empleado']))->count();
        $logisticsUsers = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'supervisor_logistica'))->count();
        $recentAuditLogs = AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count();
        $recentOrders = Pedido::query()->where('created_at', '>=', now()->subDays(7))->count();
        $paymentMethodsConfigured = [
            'pos' => (bool) config('atlantia.payments.pos.enabled', false) && trim((string) config('atlantia.payments.pos.provider')) !== '',
            'transfer' => trim((string) config('atlantia.payments.transfer.bank_name')) !== ''
                && trim((string) config('atlantia.payments.transfer.account_number')) !== '',
            'cash' => (float) config('atlantia.payments.cash.max_change_bill', 0) > 0,
        ];

        $actors = [
            'vendedor' => [
                'status' => $approvedVendors > 0 && $activeProducts > 0 && $inventoryCoverage === 0 ? 'ok' : 'error',
                'checks' => [
                    'vendors_aprobados' => $approvedVendors,
                    'productos_activos' => $activeProducts,
                    'productos_sin_inventario' => $inventoryCoverage,
                    'pedidos_recientes' => $recentOrders,
                ],
                'manual_required' => [
                    'Aceptar pedido real desde panel vendedor',
                    'Preparar pedido y medir tiempos reales',
                    'Cancelar por falta de inventario',
                    'Coordinar pedido listo con logistica',
                ],
            ],
            'cliente' => [
                'status' => $activeZones > 0 && ! in_array(false, $paymentMethodsConfigured, true) ? 'ok' : 'error',
                'checks' => [
                    'zonas_activas' => $activeZones,
                    'checkout_pos_configurado' => $paymentMethodsConfigured['pos'],
                    'checkout_transferencia_configurada' => $paymentMethodsConfigured['transfer'],
                    'checkout_efectivo_configurado' => $paymentMethodsConfigured['cash'],
                ],
                'manual_required' => [
                    'Checkout real con POS en entrega',
                    'Checkout real con transferencia en entrega',
                    'Seguimiento de pedido con cambios de estado',
                    'Reclamo o reintento de entrega con notificacion',
                ],
            ],
            'admin' => [
                'status' => $adminUsers > 0 && $supportUsers > 0 && $logisticsUsers > 0 ? 'ok' : 'error',
                'checks' => [
                    'admins_activos' => $adminUsers,
                    'soporte_activo' => $supportUsers,
                    'logistica_activa' => $logisticsUsers,
                    'auditoria_7_dias' => $recentAuditLogs,
                ],
                'manual_required' => [
                    'Reasignar pedido en simultaneo',
                    'Conciliar cobro en efectivo y retiro',
                    'Atender ticket de soporte real',
                    'Validar reportes y auditoria con multiples pedidos',
                ],
            ],
        ];

        $status = collect($actors)->contains(fn (array $actor): bool => $actor['status'] === 'error') ? 'error' : 'warn';

        return [
            'status' => $status,
            'actors' => $actors,
            'notes' => [
                'Las verificaciones manuales siguen siendo obligatorias con datos reales antes de liberar produccion.',
                'El estado "ok" aqui indica estructura lista, no reemplaza una corrida punta a punta en ambiente staging o piloto.',
            ],
        ];
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function suspiciousRows(iterable $rows, array $fields): array
    {
        $patterns = ['@atlantia.test', '@example.com', '.test', 'demo', 'sandbox', 'sample', 'prueba'];

        return collect($rows)
            ->filter(function ($row) use ($fields, $patterns): bool {
                foreach ($fields as $field) {
                    $value = mb_strtolower((string) data_get($row, $field));

                    foreach ($patterns as $pattern) {
                        if ($value !== '' && str_contains($value, $pattern)) {
                            return true;
                        }
                    }
                }

                return false;
            })
            ->map(function ($row) use ($fields): array {
                $payload = ['id' => data_get($row, 'id')];

                foreach ($fields as $field) {
                    $payload[$field] = data_get($row, $field);
                }

                return $payload;
            })
            ->values()
            ->all();
    }
}
