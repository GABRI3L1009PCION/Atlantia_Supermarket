<?php

namespace App\Services\Catalogo;

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
     * @param array<string, mixed> $filtros
     * @return LengthAwarePaginator
     */
    public function catalogo(array $filtros = []): LengthAwarePaginator
    {
        $query = Producto::query()
            ->with(['imagenPrincipal', 'categoria', 'vendor', 'inventario'])
            ->publicados();

        $busqueda = trim((string)($filtros['q'] ?? $filtros['buscar'] ?? ''));

        if ($busqueda !== '') {
            $query->where(function (Builder $builder) use ($busqueda): void {
                $builder
                    ->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('sku', 'like', '%' . $busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $busqueda . '%');
            });
        }

        if (!empty($filtros['categoria_id'])) {
            $query->where('categoria_id', (int) $filtros['categoria_id']);
        }

        $orden = (string)($filtros['orden'] ?? 'recientes');

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
                $query->latest();
                break;
        }

        $perPage = (int)($filtros['per_page'] ?? 12);

        if ($perPage < 1) {
            $perPage = 12;
        }

        if ($perPage > 48) {
            $perPage = 48;
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
