<?php

namespace App\Services\Admin;

use App\Models\Dte\DteFactura;
use App\Models\Ml\FraudAlert;
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
        return [
            'overview' => [
                'clientes_registrados' => User::query()->role('cliente')->count(),
                'ingresos_totales' => (float) Pedido::query()->padres()->sum('total'),
                'productos_disponibles' => Producto::query()->publicados()->count(),
                'ordenes_completadas' => Pedido::query()->where('estado', 'entregado')->count(),
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
            'recent_orders' => $this->recentOrders(),
            'monthly_sales' => $this->monthlySales(),
            'notifications' => $this->notifications(),
            'courier_status' => $this->courierStatus(),
            'quick_links' => $this->quickLinks(),
        ];
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
