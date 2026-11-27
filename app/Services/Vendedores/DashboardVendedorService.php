<?php

namespace App\Services\Vendedores;

use App\Models\Inventario;
use App\Models\Ml\RestockSuggestion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommission;
use App\Support\HorarioTienda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la vista general del vendedor, pensados para el día a día de la tienda.
 */
class DashboardVendedorService
{
    /** Pedidos cancelados o rechazados no son venta. */
    private const NO_VENTA = ['cancelado', 'rechazado'];

    /** Pedidos que el vendedor todavía tiene que atender. */
    private const POR_ATENDER = ['pendiente', 'confirmado', 'en_revision', 'preparando', 'listo_para_entrega'];

    /**
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        $vendor = $user->vendor;
        $vendorId = $vendor?->id;
        $hoy = today();
        $inicioMes = now()->startOfMonth();

        $ventasHoy = $this->ventas($vendorId, $hoy, $hoy->copy()->endOfDay());
        $ventasAyer = $this->ventas($vendorId, $hoy->copy()->subDay(), $hoy->copy()->subDay()->endOfDay());
        $ventasMes = $this->ventas($vendorId, $inicioMes, now());
        /* Mismo tramo del mes anterior, para comparar de forma justa. */
        $ventasMesAnterior = $this->ventas($vendorId, $inicioMes->copy()->subMonth(), now()->subMonth());
        $pedidosMes = $this->baseVentas($vendorId)->whereBetween('created_at', [$inicioMes, now()])->count();

        return [
            'tienda' => [
                'nombre' => $vendor?->business_name ?? 'Mi tienda',
                'estado' => HorarioTienda::estado($vendor),
                'url_publica' => $vendor?->slug ? route('comercios.show', $vendor->slug) : null,
            ],
            'kpis' => [
                'ventas_hoy' => ['valor' => $ventasHoy, 'cambio' => $this->cambio($ventasHoy, $ventasAyer), 'compara' => 'vs. ayer'],
                'ventas_mes' => ['valor' => $ventasMes, 'cambio' => $this->cambio($ventasMes, $ventasMesAnterior), 'compara' => 'vs. mes pasado'],
                'pedidos_mes' => ['valor' => $pedidosMes, 'cambio' => null, 'compara' => 'en el mes'],
                'ticket_promedio' => ['valor' => $pedidosMes > 0 ? round($ventasMes / $pedidosMes, 2) : 0.0, 'cambio' => null, 'compara' => 'por pedido'],
            ],
            'por_atender' => Pedido::query()
                ->where('vendor_id', $vendorId)
                ->whereIn('estado', self::POR_ATENDER)
                ->oldest()
                ->limit(6)
                ->get(['uuid', 'numero_pedido', 'total', 'estado', 'created_at']),
            'total_por_atender' => Pedido::query()->where('vendor_id', $vendorId)->whereIn('estado', self::POR_ATENDER)->count(),
            'ventas_dias' => $this->ventasPorDia($vendorId, 14),
            'top_productos' => $this->topProductos($vendorId),
            'stock_bajo' => Inventario::query()
                ->with('producto:id,nombre,uuid')
                ->whereHas('producto', fn ($query) => $query->where('vendor_id', $vendorId))
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->orderBy('stock_actual')
                ->limit(5)
                ->get(),
            'resumen' => [
                'productos_publicados' => Producto::query()->where('vendor_id', $vendorId)->publicados()->count(),
                'comisiones_pendientes' => VendorCommission::query()->where('vendor_id', $vendorId)->whereIn('estado', ['pendiente', 'facturada', 'vencida'])->count(),
                'sugerencias_reabasto' => RestockSuggestion::query()->where('vendor_id', $vendorId)->where('aceptada', false)->count(),
            ],
            'primeros_pasos' => $this->primerosPasos($vendor),
        ];
    }

    private function baseVentas(?int $vendorId)
    {
        return Pedido::query()->where('vendor_id', $vendorId)->whereNotIn('estado', self::NO_VENTA);
    }

    private function ventas(?int $vendorId, Carbon $desde, Carbon $hasta): float
    {
        return round((float) $this->baseVentas($vendorId)->whereBetween('created_at', [$desde, $hasta])->sum('total'), 2);
    }

    private function cambio(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * @return Collection<int, array{fecha: Carbon, total: float}>
     */
    private function ventasPorDia(?int $vendorId, int $dias): Collection
    {
        $desde = today()->subDays($dias - 1);
        $totales = $this->baseVentas($vendorId)
            ->where('created_at', '>=', $desde)
            ->get(['total', 'created_at'])
            ->groupBy(fn (Pedido $pedido): string => $pedido->created_at->toDateString())
            ->map(fn (Collection $grupo): float => (float) $grupo->sum('total'));

        return collect(range(0, $dias - 1))->map(function (int $i) use ($desde, $totales): array {
            $fecha = $desde->copy()->addDays($i);

            return ['fecha' => $fecha, 'total' => round((float) ($totales[$fecha->toDateString()] ?? 0), 2)];
        });
    }

    /**
     * @return Collection<int, object>
     */
    private function topProductos(?int $vendorId): Collection
    {
        return DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedidos.vendor_id', $vendorId)
            ->whereNotIn('pedidos.estado', self::NO_VENTA)
            ->where('pedidos.created_at', '>=', now()->subDays(30))
            ->groupBy('pedido_items.producto_id', 'pedido_items.producto_nombre_snapshot')
            ->orderByDesc(DB::raw('SUM(pedido_items.cantidad)'))
            ->limit(5)
            ->get([
                'pedido_items.producto_nombre_snapshot as nombre',
                DB::raw('SUM(pedido_items.cantidad) as cantidad'),
                DB::raw('SUM(pedido_items.subtotal) as venta'),
            ]);
    }

    /**
     * Guía para dejar lista una tienda nueva. Desaparece cuando todo está completo.
     *
     * @return array<int, array{titulo: string, detalle: string, hecho: bool, url: string}>
     */
    private function primerosPasos(?Vendor $vendor): array
    {
        if ($vendor === null) {
            return [];
        }

        return [
            [
                'titulo' => 'Sube tus productos',
                'detalle' => 'Con foto, precio y cantidad disponible.',
                'hecho' => $vendor->productos()->exists(),
                'url' => route('vendedor.productos.index'),
            ],
            [
                'titulo' => 'Elige dónde entregas',
                'detalle' => 'Las colonias a las que llegan tus pedidos.',
                'hecho' => $vendor->deliveryZones()->exists(),
                'url' => route('vendedor.zonas-entrega.index'),
            ],
            [
                'titulo' => 'Revisa tu horario',
                'detalle' => 'A qué hora abres y cierras.',
                'hecho' => filled($vendor->hora_apertura) && filled($vendor->hora_cierre),
                'url' => route('vendedor.operacion.edit'),
            ],
            [
                'titulo' => 'Completa tus datos de facturación',
                'detalle' => 'Para emitir facturas a tus clientes.',
                'hecho' => ! $vendor->has_nit || $vendor->fiscalProfile()->exists(),
                'url' => route('vendedor.perfil-fiscal.edit'),
            ],
        ];
    }
}
