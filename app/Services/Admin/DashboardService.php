<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Dte\DteFactura;
use App\Models\Ml\FraudAlert;
use App\Models\Ml\MlModelVersion;
use App\Models\Ml\MlTrainingJob;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de metricas consolidadas para administracion.
 */
class DashboardService
{
    /**
     * Devuelve metricas operativas del marketplace.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        $fraudAlerts = FraudAlert::query()->where('resuelta', false)->count();
        $dteRejected = DteFactura::query()->where('estado', 'rechazado')->count();
        $pedidoMetrics = $this->pedidoMetrics();
        $vendorMetrics = $this->vendorMetrics();

        return [
            'overview' => [
                'pedidos_hoy' => $pedidoMetrics['pedidos_hoy'],
                'ventas_hoy' => $pedidoMetrics['ventas_hoy'],
                'ticket_promedio' => $pedidoMetrics['ticket_promedio'],
                'tasa_entrega' => $pedidoMetrics['tasa_entrega'],
            ],
            'operacion' => [
                'ventas_hoy' => $pedidoMetrics['ventas_hoy'],
                'pedidos_hoy' => $pedidoMetrics['pedidos_hoy'],
                'pedidos_pendientes' => $pedidoMetrics['pedidos_pendientes'],
                'vendedores_pendientes' => $vendorMetrics['pending'],
                'vendedores_activos' => $vendorMetrics['approved'],
                'dte_rechazados' => $dteRejected,
                'alertas_fraude_abiertas' => $fraudAlerts,
            ],
            'alerts' => [
                'total' => $fraudAlerts + $dteRejected,
                'fraud' => $fraudAlerts,
                'dte_rejected' => $dteRejected,
                'stock_low' => Producto::query()->whereHas('inventario', function ($query): void {
                    $query->whereColumn('stock_actual', '<=', 'stock_minimo');
                })->count(),
                'vendors_pending' => $vendorMetrics['pending'],
                'ml_status' => 'OK',
            ],
            'recent_orders' => $this->recentOrders(),
            'monthly_sales' => $this->monthlySales(),
            'notifications' => $this->notifications(),
            'courier_status' => $this->courierStatus(),
            'quick_links' => $this->quickLinks(),
        ];
    }

    /**
     * Devuelve metricas de gobierno para super administracion.
     *
     * @return array<string, mixed>
     */
    public function superAdminMetrics(User $user): array
    {
        $roleCounts = $this->roleCounts();
        $vendors = Vendor::query()->count();
        $products = Producto::query()->count();

        return [
            'platform' => [
                'environment' => app()->environment(),
                'users' => User::query()->count(),
                'admins' => $roleCounts['admin'],
                'super_admins' => $roleCounts['super_admin'],
                'vendors' => $vendors,
                'products' => $products,
            ],
            'services' => $this->serviceReadiness(),
            'release' => [
                'current' => config('app.version', '1.0.0'),
                'environment' => app()->environment(),
                'branch' => (string) config('app.branch', 'local'),
                'pipeline' => [
                    ['label' => 'Codigo', 'status' => 'lista', 'detail' => 'repositorio conectado'],
                    ['label' => 'Migraciones', 'status' => 'lista', 'detail' => 'estructura definida'],
                    ['label' => 'Datos reales', 'status' => 'espera', 'detail' => 'carga operativa'],
                    ['label' => 'Aprobacion', 'status' => 'espera', 'detail' => 'super admin'],
                    ['label' => 'Produccion', 'status' => 'pendiente', 'detail' => 'despliegue final'],
                ],
            ],
            'branches' => collect([
                ['name' => 'Atlantia Central', 'status' => 'activa', 'orders' => Pedido::query()->count()],
                ['name' => 'Puerto Barrios', 'status' => 'configurada', 'orders' => Pedido::query()->whereHas('direccion', function ($query): void {
                    $query->where('municipio', 'Puerto Barrios');
                })->count()],
                ['name' => 'Santo Tomas de Castilla', 'status' => 'configurada', 'orders' => Pedido::query()->whereHas('direccion', function ($query): void {
                    $query->where('municipio', 'like', 'Santo Tom%');
                })->count()],
            ]),
            'audit' => AuditLog::query()->latest()->limit(4)->get(),
            'models' => MlModelVersion::query()->latest()->limit(3)->get(),
            'training_jobs' => MlTrainingJob::query()->latest()->limit(3)->get(),
            'counts' => [
                'users' => User::query()->count(),
                'admins' => $roleCounts['admin'],
                'vendors' => Vendor::query()->count(),
                'products' => Producto::query()->count(),
            ],
        ];
    }

    /**
     * Agrupa metricas frecuentes de pedidos en una sola consulta.
     *
     * @return array<string, float|int>
     */
    private function pedidoMetrics(): array
    {
        $today = today()->toDateString();
        $row = Pedido::query()
            ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as pedidos_hoy', [$today])
            ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN total ELSE 0 END) as ventas_hoy', [$today])
            ->selectRaw('AVG(CASE WHEN pedido_padre_id IS NULL THEN total ELSE NULL END) as ticket_promedio')
            ->selectRaw("SUM(CASE WHEN estado IN ('pendiente', 'confirmado') THEN 1 ELSE 0 END) as pedidos_pendientes")
            ->selectRaw("SUM(CASE WHEN estado IN ('entregado', 'cancelado') THEN 1 ELSE 0 END) as pedidos_cerrados")
            ->selectRaw("SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as pedidos_entregados")
            ->first();

        $cerrados = (int) ($row?->pedidos_cerrados ?? 0);

        return [
            'pedidos_hoy' => (int) ($row?->pedidos_hoy ?? 0),
            'ventas_hoy' => round((float) ($row?->ventas_hoy ?? 0), 2),
            'ticket_promedio' => round((float) ($row?->ticket_promedio ?? 0), 2),
            'pedidos_pendientes' => (int) ($row?->pedidos_pendientes ?? 0),
            'tasa_entrega' => $cerrados === 0
                ? 0.0
                : round(((int) ($row?->pedidos_entregados ?? 0) / $cerrados) * 100, 2),
        ];
    }

    /**
     * Agrupa vendedores por estado para evitar conteos repetidos.
     *
     * @return array<string, int>
     */
    private function vendorMetrics(): array
    {
        return Vendor::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $value): int => (int) $value)
            ->union(['pending' => 0, 'approved' => 0])
            ->all();
    }

    /**
     * Agrupa conteos de roles administrativos en una sola consulta.
     *
     * @return array<string, int>
     */
    private function roleCounts(): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('roles.name, COUNT(*) as total')
            ->where('model_type', User::class)
            ->whereIn('roles.name', ['admin', 'super_admin'])
            ->groupBy('roles.name')
            ->pluck('total', 'roles.name')
            ->map(fn (mixed $value): int => (int) $value)
            ->union(['admin' => 0, 'super_admin' => 0])
            ->all();
    }

    /**
     * Evalua si las integraciones principales tienen configuracion activa.
     *
     * @return Collection<int, array<string, string>>
     */
    private function serviceReadiness(): Collection
    {
        return collect([
            [
                'name' => 'Aplicacion Laravel',
                'detail' => 'Entorno ' . app()->environment(),
                'status' => 'operativo',
            ],
            [
                'name' => 'Base de datos',
                'detail' => 'Conexion ' . config('database.default'),
                'status' => config('database.default') !== null ? 'operativo' : 'pendiente',
            ],
            [
                'name' => 'Colas y jobs',
                'detail' => 'Driver ' . config('queue.default'),
                'status' => config('queue.default') !== 'sync' ? 'operativo' : 'configurar',
            ],
            [
                'name' => 'Busqueda',
                'detail' => 'Scout ' . (string) config('scout.driver', 'database'),
                'status' => (string) config('scout.driver', 'database') === 'meilisearch' ? 'operativo' : 'configurar',
            ],
            [
                'name' => 'FEL INFILE',
                'detail' => config('services.infile.base_url') ? 'endpoint configurado' : 'sin endpoint',
                'status' => config('services.infile.base_url') ? 'operativo' : 'pendiente',
            ],
            [
                'name' => 'Microservicio ML',
                'detail' => config('services.ml.base_url') ? 'endpoint configurado' : 'sin endpoint',
                'status' => config('services.ml.base_url') ? 'operativo' : 'pendiente',
            ],
        ]);
    }

    /**
     * Ultimos pedidos para panel ejecutivo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recentOrders(): Collection
    {
        return Pedido::query()
            ->with('cliente')
            ->padres()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function (Pedido $pedido): array {
                return [
                    'numero' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente?->name ?? 'Cliente no disponible',
                    'total' => (float) $pedido->total,
                    'estado' => $pedido->estado,
                    'fecha' => optional($pedido->created_at)->format('d/m/Y H:i'),
                ];
            });
    }

    /**
     * Ventas mensuales para grafico resumido.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function monthlySales(): Collection
    {
        $periodExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $rows = Pedido::query()
            ->selectRaw("{$periodExpression} as periodo")
            ->selectRaw('SUM(total) as total')
            ->padres()
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->keyBy('periodo');

        $max = max(1.0, (float) $rows->max('total'));

        return collect(range(5, 0))
            ->reverse()
            ->map(function (int $offset) use ($rows, $max): array {
                $date = now()->subMonths($offset);
                $periodo = $date->format('Y-m');
                $total = (float) ($rows->get($periodo)->total ?? 0);

                return [
                    'label' => $date->format('M Y'),
                    'total' => $total,
                    'width' => max(8, (int) round(($total / $max) * 100)),
                ];
            })
            ->values();
    }

    /**
     * Resumen de alertas administrativas.
     *
     * @return Collection<int, array<string, string>>
     */
    private function notifications(): Collection
    {
        $items = collect();

        $vendedoresPendientes = Vendor::query()->pending()->count();
        if ($vendedoresPendientes > 0) {
            $items->push([
                'title' => 'Solicitudes de vendedores pendientes',
                'description' => "Hay {$vendedoresPendientes} vendedores esperando aprobacion administrativa.",
            ]);
        }

        $alertasFraude = FraudAlert::query()->where('resuelta', false)->count();
        if ($alertasFraude > 0) {
            $items->push([
                'title' => 'Alertas de fraude abiertas',
                'description' => "Existen {$alertasFraude} alertas pendientes de revision operativa.",
            ]);
        }

        $dteRechazados = DteFactura::query()->where('estado', 'rechazado')->count();
        if ($dteRechazados > 0) {
            $items->push([
                'title' => 'DTE rechazados',
                'description' => "Se detectaron {$dteRechazados} DTE rechazados que requieren seguimiento.",
            ]);
        }

        return $items;
    }

    /**
     * Estado resumido de repartidores.
     *
     * @return array<string, mixed>
     */
    private function courierStatus(): array
    {
        $repartidores = User::query()->role('repartidor')->count();
        $pedidosEnRuta = Pedido::query()->where('estado', 'en_ruta')->count();

        return [
            'total' => $repartidores,
            'en_ruta' => $pedidosEnRuta,
            'disponibles' => max(0, $repartidores - $pedidosEnRuta),
        ];
    }

    /**
     * Accesos rapidos operativos del dashboard.
     *
     * @return Collection<int, array<string, string>>
     */
    private function quickLinks(): Collection
    {
        return collect([
            [
                'title' => 'Usuarios',
                'description' => 'Control de cuentas, estados y perfiles del marketplace.',
                'route' => route('admin.usuarios.index'),
            ],
            [
                'title' => 'Roles y permisos',
                'description' => 'Seguridad, perfiles operativos y accesos escalables.',
                'route' => route('admin.roles-permisos.index'),
            ],
            [
                'title' => 'Vendedores',
                'description' => 'Aprobacion, suspension y seguimiento comercial.',
                'route' => route('admin.vendedores.index'),
            ],
            [
                'title' => 'Pedidos',
                'description' => 'Supervision de compras, estados y cumplimiento.',
                'route' => route('admin.pedidos.index'),
            ],
            [
                'title' => 'Comisiones',
                'description' => 'Cierre comercial, estados de cobro y seguimiento mensual.',
                'route' => route('admin.comisiones.index'),
            ],
            [
                'title' => 'DTE y FEL',
                'description' => 'Seguimiento fiscal, rechazo, reintento y anulacion controlada.',
                'route' => route('admin.dte.index'),
            ],
            [
                'title' => 'Antifraude',
                'description' => 'Revision de alertas, casos abiertos y resolucion operativa.',
                'route' => route('admin.antifraude.index'),
            ],
            [
                'title' => 'Monitor ML',
                'description' => 'Modelos, drift, latencia y salud del ecosistema predictivo.',
                'route' => route('admin.ml.monitor'),
            ],
        ]);
    }
}
