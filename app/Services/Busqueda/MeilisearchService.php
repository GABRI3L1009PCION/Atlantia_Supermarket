<?php

namespace App\Services\Busqueda;

use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
            ->publicados();

        $this->applyEloquentFilters($builder, $filters);

        return $builder
            ->orderByRaw('COALESCE(precio_oferta, precio_base) asc')
            ->paginate($perPage);
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
            ->when($filters['precio_min'] ?? null, function (Builder $builder, $precio): void {
                $builder->whereRaw('COALESCE(precio_oferta, precio_base) >= ?', [$precio]);
            })
            ->when($filters['precio_max'] ?? null, function (Builder $builder, $precio): void {
                $builder->whereRaw('COALESCE(precio_oferta, precio_base) <= ?', [$precio]);
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
            'vendor_id' => $filters['vendor_id'] ?? null,
            'municipio' => $filters['municipio'] ?? null,
            'precio_min' => $filters['precio_min'] ?? null,
            'precio_max' => $filters['precio_max'] ?? null,
            'requiere_refrigeracion' => $filters['requiere_refrigeracion'] ?? null,
        ];
    }
}
