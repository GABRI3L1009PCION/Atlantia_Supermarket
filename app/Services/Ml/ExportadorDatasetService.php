<?php

namespace App\Services\Ml;

use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Servicio de exportacion de datasets para entrenamiento ML.
 */
class ExportadorDatasetService
{
    /**
     * Exporta ventas historicas por producto.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function ventas(array $filters = []): Collection
    {
        return PedidoItem::query()
            ->with(['pedido', 'producto.vendor', 'producto.categoria'])
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->whereHas(
                'producto',
                fn ($nested) => $nested->where('vendor_id', $vendorId)
            ))
            ->when($filters['desde'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filters['hasta'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->get()
            ->map(fn (PedidoItem $item) => [
                'fecha' => $item->created_at?->toDateString(),
                'producto_id' => $item->producto_id,
                'vendor_id' => $item->producto?->vendor_id,
                'categoria_id' => $item->producto?->categoria_id,
                'cantidad' => $item->cantidad,
                'subtotal' => (float) $item->subtotal,
                'municipio' => $item->pedido?->direccion?->municipio,
            ]);
    }

    /**
     * Exporta catalogo activo para recomendaciones.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function catalogo(): Collection
    {
        return Producto::query()
            ->with(['categoria', 'vendor', 'inventario'])
            ->publicados()
            ->get()
            ->map(fn (Producto $producto) => [
                'producto_id' => $producto->id,
                'vendor_id' => $producto->vendor_id,
                'categoria_id' => $producto->categoria_id,
                'nombre' => $producto->nombre,
                'precio' => (float) ($producto->precio_oferta ?? $producto->precio_base),
                'municipio' => $producto->vendor?->municipio,
                'stock_actual' => $producto->inventario?->stock_actual,
            ]);
    }
}
