<?php

namespace App\Services\Catalogo;

use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CatalogoService
{
    public function catalogo(array $filtros = []): LengthAwarePaginator
    {
        $query = Producto::query();

        $relations = [];

        foreach (['imagenPrincipal', 'imagenes', 'categoria', 'vendedor'] as $relation) {
            if (method_exists(Producto::class, $relation)) {
                $relations[] = $relation;
            }
        }

        if ($relations !== []) {
            $query->with($relations);
        }

        $busqueda = trim((string)($filtros['q'] ?? $filtros['buscar'] ?? ''));

        if ($busqueda !== '') {
            $query->where(function (Builder $builder) use ($busqueda): void {
                foreach (['nombre', 'sku', 'descripcion', 'descripcion_corta'] as $column) {
                    if (Schema::hasColumn('productos', $column)) {
                        $builder->orWhere($column, 'like', '%' . $busqueda . '%');
                    }
                }
            });
        }

        if (!empty($filtros['categoria_id']) && Schema::hasColumn('productos', 'categoria_id')) {
            $query->where('categoria_id', (int) $filtros['categoria_id']);
        }

        if (array_key_exists('activo', $filtros) && Schema::hasColumn('productos', 'activo')) {
            $query->where('activo', filter_var($filtros['activo'], FILTER_VALIDATE_BOOLEAN));
        } elseif (Schema::hasColumn('productos', 'activo')) {
            $query->where('activo', true);
        }

        $orden = (string)($filtros['orden'] ?? 'recientes');

        switch ($orden) {
            case 'precio_asc':
                if (Schema::hasColumn('productos', 'precio')) {
                    $query->orderBy('precio');
                } elseif (Schema::hasColumn('productos', 'precio_venta')) {
                    $query->orderBy('precio_venta');
                } else {
                    $query->latest();
                }
                break;

            case 'precio_desc':
                if (Schema::hasColumn('productos', 'precio')) {
                    $query->orderByDesc('precio');
                } elseif (Schema::hasColumn('productos', 'precio_venta')) {
                    $query->orderByDesc('precio_venta');
                } else {
                    $query->latest();
                }
                break;

            case 'nombre':
                if (Schema::hasColumn('productos', 'nombre')) {
                    $query->orderBy('nombre');
                } else {
                    $query->latest();
                }
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
