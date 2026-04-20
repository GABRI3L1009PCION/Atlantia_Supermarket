<?php

namespace App\Services\Catalogo;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio administrativo de productos.
 */
class ProductoAdminService
{
    /**
     * Pagina productos globales.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Producto::query()
            ->with(['vendor', 'categoria', 'inventario'])
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('is_active', $estado === 'activo'))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Devuelve detalle administrativo.
     */
    public function detail(Producto $producto): Producto
    {
        return $producto->load(['vendor.fiscalProfile', 'categoria', 'inventario', 'imagenes', 'resenas']);
    }

    /**
     * Modera estado y visibilidad del producto.
     *
     * @param array<string, mixed> $data
     */
    public function moderate(Producto $producto, array $data, User $user): Producto
    {
        $producto->update(collect($data)->only(['is_active', 'visible_catalogo'])->all());

        return $producto->refresh();
    }
}

