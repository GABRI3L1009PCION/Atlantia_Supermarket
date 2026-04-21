<?php

namespace App\Services\Vendedores;

use App\Models\Inventario;
use App\Models\Ml\RestockSuggestion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\VendorCommission;
use App\Models\User;

/**
 * Servicio de metricas del vendedor.
 */
class DashboardVendedorService
{
    /**
     * Devuelve metricas comerciales del vendedor.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        $vendorId = $user->vendor?->id;

        return [
            'overview' => [
                'ventas_hoy' => (float) Pedido::query()->where('vendor_id', $vendorId)->whereDate('created_at', today())->sum('total'),
                'ventas_mes' => (float) Pedido::query()->where('vendor_id', $vendorId)->whereMonth('created_at', now()->month)->sum('total'),
                'pedidos_pendientes' => Pedido::query()->where('vendor_id', $vendorId)->whereIn('estado', ['pendiente', 'confirmado'])->count(),
                'productos_publicados' => Producto::query()->where('vendor_id', $vendorId)->publicados()->count(),
            ],
            'operacion' => [
                'productos_activos' => Producto::query()->where('vendor_id', $vendorId)->active()->count(),
                'stock_bajo' => Inventario::query()
                    ->whereHas('producto', fn ($query) => $query->where('vendor_id', $vendorId))
                    ->whereColumn('stock_actual', '<=', 'stock_minimo')
                    ->count(),
                'comisiones_pendientes' => VendorCommission::query()
                    ->where('vendor_id', $vendorId)
                    ->whereIn('estado', ['pendiente', 'facturada', 'vencida'])
                    ->count(),
                'sugerencias_reabasto' => RestockSuggestion::query()
                    ->where('vendor_id', $vendorId)
                    ->where('aceptada', false)
                    ->count(),
            ],
            'pedidos_recientes' => Pedido::query()
                ->where('vendor_id', $vendorId)
                ->latest()
                ->limit(6)
                ->get(['uuid', 'numero_pedido', 'total', 'estado', 'created_at']),
            'quick_links' => [
                ['title' => 'Productos', 'description' => 'Gestiona catalogo, precios y visibilidad.', 'route' => route('vendedor.productos.index')],
                ['title' => 'Inventario', 'description' => 'Actualiza stock y controla minimos.', 'route' => route('vendedor.inventario.index')],
                ['title' => 'Pedidos', 'description' => 'Atiende pedidos recibidos por tu tienda.', 'route' => route('vendedor.pedidos.index')],
                ['title' => 'Prediccion ML', 'description' => 'Consulta demanda esperada y reabasto.', 'route' => route('vendedor.predicciones.index')],
            ],
        ];
    }
}
