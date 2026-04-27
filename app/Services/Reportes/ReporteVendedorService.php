<?php

namespace App\Services\Reportes;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de reportes para vendedores.
 */
class ReporteVendedorService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function summary(User $user, array $filters = []): array
    {
        [$desde, $hasta] = $this->dateRange($filters);
        $agrupacion = ($filters['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $vendorId = $user->vendor?->id;

        if (! $vendorId) {
            return $this->emptySummary($desde, $hasta, $agrupacion);
        }

        $periodo = $this->periodExpression('pedidos.created_at', $agrupacion);
        $pedidosBase = Pedido::query()
            ->where('vendor_id', $vendorId)
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
            'pendientes' => (clone $pedidosBase)->whereIn('estado', ['pendiente', 'confirmado'])->count(),
            'ticket_promedio' => round((float) (clone $pedidosBase)->avg('total'), 2),
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
            'top_productos' => PedidoItem::query()
                ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
                ->where('pedidos.vendor_id', $vendorId)
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
            'stock_bajo' => Producto::query()
                ->forVendor($vendorId)
                ->with('inventario')
                ->whereHas('inventario', fn ($query) => $query->bajoMinimo())
                ->orderBy('nombre')
                ->limit(10)
                ->get(),
        ];
    }

    private function emptySummary(Carbon $desde, Carbon $hasta, string $agrupacion): array
    {
        return [
            'filters' => [
                'fecha_desde' => $desde->toDateString(),
                'fecha_hasta' => $hasta->toDateString(),
                'agrupacion' => $agrupacion,
            ],
            'ventas_total' => 0.0,
            'pedidos_total' => 0,
            'ventas_mes' => 0.0,
            'pedidos_mes' => 0,
            'pendientes' => 0,
            'ticket_promedio' => 0.0,
            'ventas_por_periodo' => collect(),
            'pedidos_por_estado' => collect(),
            'top_productos' => collect(),
            'stock_bajo' => collect(),
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
