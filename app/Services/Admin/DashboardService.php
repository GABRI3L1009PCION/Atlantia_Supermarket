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

        return [
            'overview' => [
                'pedidos_hoy' => Pedido::query()->whereDate('created_at', today())->count(),
                'ventas_hoy' => (float) Pedido::query()->whereDate('created_at', today())->sum('total'),
                'ticket_promedio' => (float) Pedido::query()->padres()->avg('total'),
                'tasa_entrega' => $this->deliveryRate(),
            ],
            'operacion' => [
                'ventas_hoy' => (float) Pedido::query()->whereDate('created_at', today())->sum('total'),
                'pedidos_hoy' => Pedido::query()->whereDate('created_at', today())->count(),
                'pedidos_pendientes' => Pedido::query()->whereIn('estado', ['pendiente', 'confirmado'])->count(),
                'vendedores_pendientes' => Vendor::query()->pending()->count(),
                'vendedores_activos' => Vendor::query()->approved()->count(),
                'dte_rechazados' => DteFactura::query()->where('estado', 'rechazado')->count(),
                'alertas_fraude_abiertas' => FraudAlert::query()->where('resuelta', false)->count(),
            ],
            'alerts' => [
                'total' => $fraudAlerts + $dteRejected,
                'fraud' => $fraudAlerts,
                'dte_rejected' => $dteRejected,
                'stock_low' => Producto::query()->whereHas('inventario', function ($query): void {
                    $query->whereColumn('stock_actual', '<=', 'stock_minimo');
                })->count(),
                'vendors_pending' => Vendor::query()->pending()->count(),
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
        $admins = User::query()->role('admin')->count();
        $superAdmins = User::query()->role('super_admin')->count();
        $vendors = Vendor::query()->count();
        $products = Producto::query()->count();

        return [
            'platform' => [
                'environment' => app()->environment(),
                'users' => User::query()->count(),
                'admins' => $admins,
                'super_admins' => $superAdmins,
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
                'admins' => User::query()->role('admin')->count(),
                'vendors' => Vendor::query()->count(),
                'products' => Producto::query()->count(),
            ],
        ];
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
     * Calcula tasa de entrega sobre pedidos cerrados.
     */
    private function deliveryRate(): float
    {
        $total = Pedido::query()->whereIn('estado', ['entregado', 'cancelado'])->count();

        if ($total === 0) {
            return 0.0;
        }

        return round((Pedido::query()->where('estado', 'entregado')->count() / $total) * 100, 2);
    }

    /**
     * Ventas mensuales para grafico resumido.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function monthlySales(): Collection
    {
        $rows = Pedido::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as periodo")
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
