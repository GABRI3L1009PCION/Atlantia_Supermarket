<?php

namespace App\Services\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio de catalogo publico de productos.
 */
class CatalogoService
{
    /**
     * Devuelve productos publicados con filtros seguros.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function catalogo(array $filtros = []): LengthAwarePaginator
    {
        $query = Producto::query()
            ->with(['imagenPrincipal', 'media', 'categoria', 'vendor', 'inventario'])
            ->publicados();

        $query->whereHas('vendor', fn (Builder $vendor) => $vendor->approved());

        if (! empty($filtros['municipio'])) {
            $query->whereHas('vendor', fn (Builder $vendor) => $vendor->where('municipio', $filtros['municipio']));
        }

        /* La tienda solo muestra productos con existencias disponibles para vender. */
        $query->whereHas('inventario', fn (Builder $stock) => $stock->whereColumn('stock_actual', '>', 'stock_reservado'));

        $busqueda = trim((string) ($filtros['q'] ?? $filtros['buscar'] ?? ''));

        if ($busqueda !== '') {
            $query->where(function (Builder $builder) use ($busqueda): void {
                $builder
                    ->where('nombre', 'like', '%'.$busqueda.'%')
                    ->orWhere('sku', 'like', '%'.$busqueda.'%')
                    ->orWhere('descripcion', 'like', '%'.$busqueda.'%');
            });
        }

        if (! empty($filtros['categoria_id'])) {
            $categoryId = (int) $filtros['categoria_id'];
            $categoryIds = Categoria::query()->active()->where('parent_id', $categoryId)->pluck('id')->prepend($categoryId);
            $query->whereIn('categoria_id', $categoryIds);
        }

        $orden = (string) ($filtros['orden'] ?? 'recientes');

        switch ($orden) {
            case 'precio_asc':
                $query->orderByRaw('COALESCE(precio_oferta, precio_base) asc');
                break;

            case 'precio_desc':
                $query->orderByRaw('COALESCE(precio_oferta, precio_base) desc');
                break;

            case 'nombre':
                $query->orderBy('nombre');
                break;

            default:
                $query->latest('publicado_at');
                break;
        }

        $perPage = (int) ($filtros['per_page'] ?? 12);

        if ($perPage < 1) {
            $perPage = 12;
        }

        if ($perPage > 48) {
            $perPage = 48;
        }

        return $query->orderBy('id')->paginate($perPage)->withQueryString();
    }
}
