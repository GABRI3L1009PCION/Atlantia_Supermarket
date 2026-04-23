<?php

namespace App\Services\Busqueda;

use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Servicio de busqueda avanzada del catalogo con Laravel Scout y Meilisearch.
 */
class MeilisearchService
{
    /**
     * Ejecuta busqueda de catalogo con filtros seguros.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function search(array $filters): array
    {
        $perPage = min(50, max(12, (int) ($filters['per_page'] ?? 24)));
        $query = trim((string) ($filters['q'] ?? ''));
        $page = (int) ($filters['page'] ?? request('page', 1));
        $cacheVersion = Cache::get('search:version', 1);
        $cacheKey = 'search:' . $cacheVersion . ':' . sha1(json_encode($this->normalizeFilters($filters)) . ":{$page}:{$perPage}");

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $filters, $perPage): array {
            $results = $query !== ''
                ? $this->searchWithScout($query, $filters, $perPage)
                : $this->searchWithEloquent($filters, $perPage);

            return [
                'items' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
                'filters' => $this->normalizeFilters($filters),
            ];
        });
    }

    /**
     * Sincroniza productos publicados hacia el indice de busqueda.
     *
     * @return int
     */
    public function reindexCatalogo(): int
    {
        $cantidad = 0;

        Producto::query()
            ->publicados()
            ->with(['categoria', 'vendor', 'inventario'])
            ->chunkById(250, function (Collection $productos) use (&$cantidad): void {
                $productos->searchable();
                $cantidad += $productos->count();
            });

        return $cantidad;
    }

    /**
     * Elimina del indice productos no visibles.
     *
     * @return int
     */
    public function purgeNoPublicados(): int
    {
        $cantidad = 0;

        Producto::query()
            ->where(function (Builder $query): void {
                $query->where('is_active', false)
                    ->orWhere('visible_catalogo', false)
                    ->orWhereNull('publicado_at');
            })
            ->chunkById(250, function (Collection $productos) use (&$cantidad): void {
                $productos->unsearchable();
                $cantidad += $productos->count();
            });

        return $cantidad;
    }

    /**
     * Configura indice Meilisearch cuando el cliente esta disponible.
     *
     * @return bool
     */
    public function configurarIndice(): bool
    {
        if (! class_exists(\Meilisearch\Client::class)) {
            return false;
        }

        $host = config('scout.meilisearch.host') ?: env('MEILISEARCH_HOST');
        $key = config('scout.meilisearch.key') ?: env('MEILISEARCH_KEY');

        if (empty($host)) {
            return false;
        }

        $client = new \Meilisearch\Client($host, $key);
        $index = $client->index((new Producto())->searchableAs());
        $index->updateFilterableAttributes([
            'vendor_id',
            'categoria_id',
            'is_active',
            'visible_catalogo',
            'precio_base',
            'precio_oferta',
        ]);
        $index->updateSortableAttributes(['precio_base', 'precio_oferta', 'id']);

        return true;
    }

    /**
     * Busca con Scout y usa Eloquent si el motor no responde.
     *
     * @param string $query
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    private function searchWithScout(string $query, array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return Producto::search($query, function ($engine, string $query, array $options) use ($filters): mixed {
                $options['filter'] = $this->buildMeilisearchFilters($filters);

                return $engine->search($query, $options);
            })
                ->query(fn (Builder $builder) => $this->applyEloquentFilters($builder, $filters))
                ->paginate($perPage);
        } catch (Throwable) {
            return $this->searchWithEloquent($filters + ['q' => $query], $perPage);
        }
    }

    /**
     * Busca con Eloquent como respaldo local y para listados sin texto.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    private function searchWithEloquent(array $filters, int $perPage): LengthAwarePaginator
    {
        $builder = Producto::query()
            ->with(['categoria', 'vendor', 'inventario', 'imagenPrincipal'])
            ->withAvg(['resenas as rating_promedio' => fn (Builder $query) => $query->where('aprobada', true)], 'calificacion')
            ->publicados();

        $this->applyEloquentFilters($builder, $filters);
        $this->applySort($builder, (string) ($filters['orden'] ?? 'relevancia'));

        return $builder->paginate($perPage);
    }

    /**
     * Aplica filtros SQL equivalentes a los filtros del indice.
     *
     * @param Builder<Producto> $builder
     * @param array<string, mixed> $filters
     * @return Builder<Producto>
     */
    private function applyEloquentFilters(Builder $builder, array $filters): Builder
    {
        $query = trim((string) ($filters['q'] ?? ''));

        return $builder
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $nested) use ($query): void {
                    $nested->where('nombre', 'like', "%{$query}%")
                        ->orWhere('descripcion', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                });
            })
            ->when($filters['categoria_id'] ?? null, fn (Builder $builder, $id) => $builder->where('categoria_id', $id))
            ->when($filters['vendor_id'] ?? null, fn (Builder $builder, $id) => $builder->where('vendor_id', $id))
            ->when(! empty($filters['categoria_ids']), fn (Builder $builder) => $builder->whereIn('categoria_id', (array) $filters['categoria_ids']))
            ->when($filters['precio_min'] ?? null, function (Builder $builder, $precio): void {
                $builder->whereRaw('COALESCE(precio_oferta, precio_base) >= ?', [$precio]);
            })
            ->when($filters['precio_max'] ?? null, function (Builder $builder, $precio): void {
                $builder->whereRaw('COALESCE(precio_oferta, precio_base) <= ?', [$precio]);
            })
            ->when($filters['rating_min'] ?? null, function (Builder $builder, $rating): void {
                $builder->whereHas('resenas', function (Builder $query) use ($rating): void {
                    $query->where('aprobada', true)
                        ->groupBy('producto_id')
                        ->havingRaw('AVG(calificacion) >= ?', [(float) $rating]);
                });
            })
            ->when(! empty($filters['en_stock']), function (Builder $builder): void {
                $builder->whereHas('inventario', function (Builder $query): void {
                    $query->whereRaw('stock_actual - stock_reservado > 0');
                });
            })
            ->when(isset($filters['requiere_refrigeracion']), function (Builder $builder) use ($filters): void {
                $builder->where('requiere_refrigeracion', (bool) $filters['requiere_refrigeracion']);
            })
            ->when($filters['municipio'] ?? null, function (Builder $builder, $municipio): void {
                $builder->whereHas('vendor', fn (Builder $query) => $query->where('municipio', $municipio));
            });
    }

    /**
     * Construye filtros para Meilisearch.
     *
     * @param array<string, mixed> $filters
     * @return array<int, string>
     */
    private function buildMeilisearchFilters(array $filters): array
    {
        $meiliFilters = ['is_active = true', 'visible_catalogo = true'];

        foreach (['categoria_id', 'vendor_id'] as $field) {
            if (! empty($filters[$field])) {
                $meiliFilters[] = "{$field} = " . (int) $filters[$field];
            }
        }

        foreach ((array) ($filters['categoria_ids'] ?? []) as $categoriaId) {
            $meiliFilters[] = 'categoria_id = ' . (int) $categoriaId;
        }

        if (! empty($filters['precio_min'])) {
            $meiliFilters[] = 'precio_base >= ' . (float) $filters['precio_min'];
        }

        if (! empty($filters['precio_max'])) {
            $meiliFilters[] = 'precio_base <= ' . (float) $filters['precio_max'];
        }

        return $meiliFilters;
    }

    /**
     * Normaliza filtros devueltos al cliente.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'categoria_id' => $filters['categoria_id'] ?? null,
            'categoria_ids' => array_values(array_filter((array) ($filters['categoria_ids'] ?? []))),
            'vendor_id' => $filters['vendor_id'] ?? null,
            'municipio' => $filters['municipio'] ?? null,
            'precio_min' => $filters['precio_min'] ?? null,
            'precio_max' => $filters['precio_max'] ?? null,
            'rating_min' => $filters['rating_min'] ?? null,
            'en_stock' => (bool) ($filters['en_stock'] ?? false),
            'orden' => $filters['orden'] ?? 'relevancia',
            'requiere_refrigeracion' => $filters['requiere_refrigeracion'] ?? null,
        ];
    }

    /**
     * Aplica orden seguro para resultados locales.
     *
     * @param Builder<Producto> $builder
     * @param string $orden
     * @return void
     */
    private function applySort(Builder $builder, string $orden): void
    {
        match ($orden) {
            'precio_asc' => $builder->orderByRaw('COALESCE(precio_oferta, precio_base) asc'),
            'precio_desc' => $builder->orderByRaw('COALESCE(precio_oferta, precio_base) desc'),
            'mas_vendido' => $builder->withCount('pedidoItems')->orderByDesc('pedido_items_count'),
            'mas_nuevo', 'recientes' => $builder->latest('publicado_at'),
            default => $builder->latest('publicado_at'),
        };
    }
}
