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
     * @param  array<string, mixed>  $filters
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
        /* La venta de la tienda es su mercadería: sin envío ni propina, y sin pedidos cancelados. */
        $venta = '(pedidos.total - COALESCE(pedidos.envio, 0) - COALESCE(pedidos.propina, 0))';
        $validos = fn (Carbon $inicio, Carbon $fin) => Pedido::query()
            ->where('vendor_id', $vendorId)
            ->whereNotIn('estado', ['cancelado', 'rechazado'])
            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()]);
        $pedidosBase = $validos($desde, $hasta);

        $ventasPorPeriodo = (clone $pedidosBase)
            ->selectRaw($periodo.' as periodo, COUNT(*) as pedidos, COALESCE(SUM('.$venta.'), 0) as total')
            ->groupBy(DB::raw($periodo))
            ->orderBy('periodo')
            ->get();
        $topProductos = PedidoItem::query()
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->where('pedidos.vendor_id', $vendorId)
            ->whereNull('pedidos.deleted_at')
            ->whereNotIn('pedidos.estado', ['cancelado', 'rechazado'])
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
            ->get();
        $ventasTotal = round((float) (clone $pedidosBase)->sum(DB::raw($venta)), 2);
        $pedidosTotal = (clone $pedidosBase)->count();
        $mejorDia = $ventasPorPeriodo->sortByDesc('total')->first();
        $dias = max(1, (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1);

        /* Periodo anterior del mismo largo, para comparar de verdad. */
        $anteriorFin = $desde->copy()->subDay();
        $anteriorInicio = $anteriorFin->copy()->subDays($dias - 1);
        $ventasAnterior = round((float) $validos($anteriorInicio, $anteriorFin)->sum(DB::raw($venta)), 2);

        return [
            'filters' => [
                'fecha_desde' => $desde->toDateString(),
                'fecha_hasta' => $hasta->toDateString(),
                'agrupacion' => $agrupacion,
            ],
            'ventas_total' => $ventasTotal,
            'pedidos_total' => $pedidosTotal,
            'ventas_mes' => $ventasTotal,
            'pedidos_mes' => $pedidosTotal,
            'cancelados' => Pedido::query()->where('vendor_id', $vendorId)->whereIn('estado', ['cancelado', 'rechazado'])
                ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])->count(),
            'pendientes' => (clone $pedidosBase)->whereIn('estado', ['pendiente', 'en_revision', 'confirmado', 'preparando', 'listo_para_entrega'])->count(),
            'ticket_promedio' => $pedidosTotal > 0 ? round($ventasTotal / $pedidosTotal, 2) : 0.0,
            'productos_vendidos' => (int) $topProductos->sum('unidades'),
            'promedio_diario' => round($ventasTotal / $dias, 2),
            'comparacion' => [
                'anterior' => $ventasAnterior,
                'desde' => $anteriorInicio->toDateString(),
                'hasta' => $anteriorFin->toDateString(),
                'cambio' => $ventasAnterior > 0 ? round((($ventasTotal - $ventasAnterior) / $ventasAnterior) * 100, 1) : null,
            ],
            'mejor_dia' => [
                'periodo' => $mejorDia?->periodo,
                'total' => round((float) ($mejorDia?->total ?? 0), 2),
            ],
            'ventas_por_periodo' => $ventasPorPeriodo,
            'pedidos_por_estado' => Pedido::query()
                ->where('vendor_id', $vendorId)
                ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
                ->select('estado', DB::raw('COUNT(*) as pedidos'))
                ->groupBy('estado')
                ->orderByDesc('pedidos')
                ->get(),
            'top_productos' => $topProductos,
            'stock_bajo' => Producto::query()
                ->forVendor($vendorId)
                ->with('inventario')
                ->whereHas('inventario', fn ($query) => $query->bajoMinimo())
                ->orderBy('nombre')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Filas para descargar las ventas del periodo (una por pedido).
     *
     * @param  array<string, mixed>  $filters
     * @return array{desde: string, hasta: string, filas: \Illuminate\Support\Collection<int, Pedido>}
     */
    public function ventasParaExportar(User $user, array $filters = []): array
    {
        [$desde, $hasta] = $this->dateRange($filters);

        return [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filas' => Pedido::query()
                ->with('items:id,pedido_id,cantidad')
                ->where('vendor_id', $user->vendor?->id)
                ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
                ->orderBy('created_at')
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
            'cancelados' => 0,
            'pendientes' => 0,
            'ticket_promedio' => 0.0,
            'comparacion' => ['anterior' => 0.0, 'desde' => null, 'hasta' => null, 'cambio' => null],
            'productos_vendidos' => 0,
            'promedio_diario' => 0.0,
            'mejor_dia' => ['periodo' => null, 'total' => 0.0],
            'ventas_por_periodo' => collect(),
            'pedidos_por_estado' => collect(),
            'top_productos' => collect(),
            'stock_bajo' => collect(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
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
