<?php

namespace App\Services\Catalogo;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de productos propios del vendedor.
 */
class ProductoVendedorService
{
    /**
     * Pagina productos del vendedor autenticado.
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return Producto::query()
            ->with(['categoria', 'inventario', 'imagenPrincipal', 'imagenes', 'media'])
            ->where('vendor_id', $user->vendor?->id)
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('nombre', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('codigo_barras', 'like', "%{$term}%");
                });
            })
            ->when($filters['categoria_id'] ?? null, fn ($query, $categoriaId) => $query->where('categoria_id', $categoriaId))
            ->when($filters['estado'] ?? null, function ($query, string $estado): void {
                match ($estado) {
                    'activo' => $query->where('is_active', true),
                    'inactivo' => $query->where('is_active', false),
                    'visible' => $query->where('visible_catalogo', true),
                    'oculto' => $query->where('visible_catalogo', false),
                    default => null,
                };
            })
            ->when($filters['stock'] ?? null, function ($query, string $stock): void {
                match ($stock) {
                    'disponible' => $query->whereHas('inventario', fn ($query) => $query->where('stock_actual', '>', 0)),
                    'bajo' => $query->whereHas('inventario', fn ($query) => $query->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_actual', '>', 0)),
                    'agotado' => $query->whereHas('inventario', fn ($query) => $query->where('stock_actual', '<=', 0)),
                    default => null,
                };
            })
            ->when(($filters['orden'] ?? 'recientes') !== 'recientes', function ($query) use ($filters): void {
                match ($filters['orden']) {
                    'nombre' => $query->orderBy('nombre'),
                    'precio_asc' => $query->orderBy('precio_base'),
                    'precio_desc' => $query->orderByDesc('precio_base'),
                    'stock_asc' => $query
                        ->join('inventarios', 'inventarios.producto_id', '=', 'productos.id')
                        ->orderBy('inventarios.stock_actual')
                        ->select('productos.*'),
                    default => $query->latest(),
                };
            }, fn ($query) => $query->latest())
            ->paginate((int) ($filters['per_page'] ?? 12))
            ->withQueryString();
    }

    /**
     * Crea producto con inventario inicial.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Producto
    {
        return DB::transaction(function () use ($user, $data): Producto {
            $producto = Producto::query()->create([
                ...collect($data)->only([
                    'categoria_id',
                    'sku',
                    'codigo_barras',
                    'nombre',
                    'descripcion',
                    'precio_base',
                    'precio_oferta',
                    'peso_gramos',
                    'unidad_medida',
                    'requiere_refrigeracion',
                    'is_active',
                    'visible_catalogo',
                ])->all(),
                'uuid' => (string) Str::uuid(),
                'vendor_id' => $user->vendor?->id,
                'slug' => Str::slug((string) $data['nombre']) . '-' . Str::lower(Str::random(6)),
                'publicado_at' => ($data['visible_catalogo'] ?? false) ? now() : null,
            ]);

            $producto->inventario()->create([
                'stock_actual' => (int) ($data['stock_actual'] ?? 0),
                'stock_reservado' => 0,
                'stock_minimo' => (int) ($data['stock_minimo'] ?? 5),
                'stock_maximo' => (int) ($data['stock_maximo'] ?? 100),
                'ultima_actualizacion' => now(),
            ]);

            $this->storeImages($producto, $data['imagenes'] ?? []);

            return $producto->load(['categoria', 'inventario', 'imagenes']);
        });
    }

    /**
     * Actualiza producto propio.
     *
     * @param array<string, mixed> $data
     */
    public function update(Producto $producto, array $data): Producto
    {
        if (isset($data['nombre'])) {
            $data['slug'] = $producto->slug ?: Str::slug((string) $data['nombre']) . '-' . Str::lower(Str::random(6));
        }

        if (($data['visible_catalogo'] ?? false) && $producto->publicado_at === null) {
            $data['publicado_at'] = now();
        }

        $producto->update(collect($data)->except(['stock_actual', 'stock_minimo', 'stock_maximo', 'imagenes'])->all());

        if (array_key_exists('stock_actual', $data) || array_key_exists('stock_minimo', $data) || array_key_exists('stock_maximo', $data)) {
            $producto->inventario()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => (int) ($data['stock_actual'] ?? $producto->inventario?->stock_actual ?? 0),
                    'stock_minimo' => (int) ($data['stock_minimo'] ?? $producto->inventario?->stock_minimo ?? 5),
                    'stock_maximo' => (int) ($data['stock_maximo'] ?? $producto->inventario?->stock_maximo ?? 100),
                    'stock_reservado' => (int) ($producto->inventario?->stock_reservado ?? 0),
                    'ultima_actualizacion' => now(),
                ]
            );
        }

        $this->storeImages($producto, $data['imagenes'] ?? []);

        return $producto->refresh();
    }

    /**
     * Elimina producto propio con soft delete.
     */
    public function delete(Producto $producto): void
    {
        $producto->update(['is_active' => false, 'visible_catalogo' => false]);
        $producto->delete();
    }

    /**
     * Guarda imagenes del producto en el disco configurado.
     *
     * @param Producto $producto
     * @param array<int, mixed> $imagenes
     * @return void
     */
    private function storeImages(Producto $producto, array $imagenes): void
    {
        if ($imagenes === []) {
            return;
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        foreach ($imagenes as $index => $imagen) {
            $path = $imagen->store('productos/' . $producto->uuid, $disk);

            $producto
                ->addMediaFromDisk($path, $disk)
                ->preservingOriginal()
                ->usingName($producto->nombre)
                ->usingFileName(basename($path))
                ->toMediaCollection('productos', $disk);

            $producto->imagenes()->create([
                'path' => $path,
                'alt_text' => $producto->nombre,
                'orden' => $index,
                'es_principal' => $index === 0,
            ]);
        }
    }
}
