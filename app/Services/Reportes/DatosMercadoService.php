<?php

namespace App\Services\Reportes;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de venta para marcas y proveedores: que se vende, cuando y en que colonia.
 * Solo cifras agregadas: nunca se exponen nombres, telefonos ni direcciones de clientes.
 */
class DatosMercadoService
{
    /**
     * @param  array{desde?: string|null, hasta?: string|null, categoria_id?: int|null, vendor_id?: int|null}  $filtros
     * @return array<string, Collection<int, object>>
     */
    public function resumen(array $filtros): array
    {
        return [
            'productos' => $this->base($filtros)
                ->selectRaw('pedido_items.producto_nombre_snapshot as producto, SUM(pedido_items.cantidad) as unidades, SUM(pedido_items.subtotal) as monto, COUNT(DISTINCT pedidos.id) as pedidos')
                ->groupBy('pedido_items.producto_nombre_snapshot')
                ->orderByDesc('unidades')
                ->limit(50)
                ->get(),
            'categorias' => $this->base($filtros)
                ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
                ->selectRaw("COALESCE(categorias.nombre, 'Sin categoria') as categoria, SUM(pedido_items.cantidad) as unidades, SUM(pedido_items.subtotal) as monto")
                ->groupBy('categorias.nombre')
                ->orderByDesc('monto')
                ->get(),
            'horas' => $this->base($filtros)
                ->selectRaw($this->expresionHora().' as hora, SUM(pedido_items.subtotal) as monto, COUNT(DISTINCT pedidos.id) as pedidos')
                ->groupByRaw($this->expresionHora())
                ->orderBy('hora')
                ->get(),
            'colonias' => $this->base($filtros)
                ->join('direcciones', 'direcciones.id', '=', 'pedidos.direccion_id')
                ->selectRaw("direcciones.municipio as municipio, COALESCE(NULLIF(direcciones.zona_o_barrio, ''), 'Sin colonia') as colonia, COUNT(DISTINCT pedidos.id) as pedidos, SUM(pedido_items.subtotal) as monto")
                ->groupBy('direcciones.municipio', 'direcciones.zona_o_barrio')
                ->havingRaw('COUNT(DISTINCT pedidos.id) >= 3')
                ->orderByDesc('monto')
                ->limit(40)
                ->get(),
        ];
    }

    /**
     * Ventas entregadas de pedidos de comercio (los hijos que llevan los productos).
     *
     * @param  array<string, mixed>  $filtros
     */
    private function base(array $filtros)
    {
        $desde = Carbon::parse($filtros['desde'] ?? now()->subDays(30)->toDateString())->startOfDay();
        $hasta = Carbon::parse($filtros['hasta'] ?? now()->toDateString())->endOfDay();

        return DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->leftJoin('productos', 'productos.id', '=', 'pedido_items.producto_id')
            ->whereNotIn('pedidos.estado', ['cancelado', 'rechazado', 'pendiente'])
            ->whereBetween('pedidos.created_at', [$desde, $hasta])
            ->when($filtros['categoria_id'] ?? null, fn ($q, $id) => $q->where('productos.categoria_id', $id))
            ->when($filtros['vendor_id'] ?? null, fn ($q, $id) => $q->where('pedidos.vendor_id', $id));
    }

    private function expresionHora(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', pedidos.created_at) AS INTEGER)"
            : 'HOUR(pedidos.created_at)';
    }
}
