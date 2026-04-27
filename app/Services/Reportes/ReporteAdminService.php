<?php

namespace App\Services\Reportes;

use App\Models\Dte\DteFactura;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de reportes administrativos.
 */
class ReporteAdminService
{
    /**
     * Devuelve resumen financiero.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        [$desde, $hasta] = $this->dateRange($filters);
        $agrupacion = ($filters['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $periodo = $this->periodExpression('pedidos.created_at', $agrupacion);

        $pedidosBase = Pedido::query()
            ->padres()
            ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()]);

        return [
            'filters' => [
                'fecha_desde' => $desde->toDateString(),
                'fecha_hasta' => $hasta->toDateString(),
                'agrupacion' => $agrupacion,
            ],
            'ventas_total' => round((float) (clone $pedidosBase)->sum('total'), 2),
            'pedidos_total' => (clone $pedidosBase)->count(),
            'ventas_mes' => round((float) (clone $pedidosBase)->sum('total'), 2),
            'pedidos_mes' => (clone $pedidosBase)->count(),
            'ticket_promedio' => round((float) (clone $pedidosBase)->avg('total'), 2),
            'vendedores_activos' => Vendor::query()->approved()->count(),
            'comisiones_pendientes' => VendorCommission::query()->where('estado', 'pendiente')->count(),
            'dtes_rechazados' => DteFactura::query()->where('estado', 'rechazado')->count(),
            'alertas_pendientes' => FraudAlert::query()->where('revisada', false)->count(),
            'ventas_por_periodo' => (clone $pedidosBase)
                ->selectRaw($periodo . ' as periodo, COUNT(*) as pedidos, COALESCE(SUM(total), 0) as total')
                ->groupBy(DB::raw($periodo))
                ->orderBy('periodo')
                ->get(),
            'pedidos_por_estado' => (clone $pedidosBase)
                ->select('estado', DB::raw('COUNT(*) as pedidos'), DB::raw('COALESCE(SUM(total), 0) as total'))
                ->groupBy('estado')
                ->orderByDesc('pedidos')
                ->get(),
            'metodos_pago' => (clone $pedidosBase)
                ->select('metodo_pago', DB::raw('COUNT(*) as pedidos'), DB::raw('COALESCE(SUM(total), 0) as total'))
                ->groupBy('metodo_pago')
                ->orderByDesc('total')
                ->get(),
            'top_productos' => PedidoItem::query()
                ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
                ->whereNull('pedidos.deleted_at')
                ->whereBetween('pedidos.created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
                ->select(
                    'pedido_items.producto_id',
                    DB::raw('MAX(pedido_items.producto_nombre_snapshot) as nombre'),
                    DB::raw('SUM(pedido_items.cantidad) as unidades'),
                    DB::raw('COALESCE(SUM(pedido_items.subtotal), 0) as total')
                )
                ->groupBy('pedido_items.producto_id')
                ->orderByDesc('unidades')
                ->limit(8)
                ->get(),
            'ingresos_por_vendedor' => Vendor::query()
                ->select(
                    'vendors.id',
                    'vendors.business_name',
                    DB::raw('COUNT(pedidos.id) as pedidos'),
                    DB::raw('COALESCE(SUM(pedidos.total), 0) as total_ventas')
                )
                ->leftJoin('pedidos', function ($join) use ($desde, $hasta): void {
                    $join->on('pedidos.vendor_id', '=', 'vendors.id')
                        ->whereNull('pedidos.deleted_at')
                        ->whereBetween('pedidos.created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()]);
                })
                ->groupBy('vendors.id', 'vendors.business_name')
                ->orderByDesc('total_ventas')
                ->limit(8)
                ->get(),
            'dtes_recientes' => DteFactura::query()->with('vendor')->latest()->limit(5)->get(),
            'alertas_recientes' => FraudAlert::query()->with(['pedido', 'user'])->latest()->limit(5)->get(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(array $filters): array
    {
        $desde = $this->parseDate($filters['fecha_desde'] ?? null, now()->startOfMonth());
        $hasta = $this->parseDate($filters['fecha_hasta'] ?? null, now());

        return $desde->lte($hasta) ? [$desde, $hasta] : [$hasta, $desde];
    }

    private function parseDate(mixed $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse((string) $value) : $fallback->copy();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function periodExpression(string $column, string $agrupacion): string
    {
        $driver = DB::connection()->getDriverName();

        if ($agrupacion === 'mes') {
            return $driver === 'sqlite'
                ? "strftime('%Y-%m', {$column})"
                : "DATE_FORMAT({$column}, '%Y-%m')";
        }

        return $driver === 'sqlite'
            ? "date({$column})"
            : "DATE({$column})";
    }
}
