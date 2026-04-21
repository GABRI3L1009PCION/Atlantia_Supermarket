<?php

namespace App\Services\Catalogo;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(fn ($builder) => $builder
                    ->where('nombre', 'like', '%' . $q . '%')
                    ->orWhere('sku', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%'));
            })
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

    /**
     * Crea un producto administrativo con inventario inicial.
     *
     * @param array<string, mixed> $data
     * @return Producto
     */
    public function create(array $data): Producto
    {
        return DB::transaction(function () use ($data): Producto {
            $producto = Producto::query()->create([
                ...collect($data)->only([
                    'vendor_id',
                    'categoria_id',
                    'sku',
                    'nombre',
                    'slug',
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
                'slug' => ($data['slug'] ?? Str::slug((string) $data['nombre'])) . '-' . Str::lower(Str::random(4)),
                'publicado_at' => ($data['visible_catalogo'] ?? false) ? now() : null,
            ]);

            $producto->inventario()->create([
                'stock_actual' => (int) $data['stock_actual'],
                'stock_reservado' => 0,
                'stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                'stock_maximo' => (int) ($data['stock_maximo'] ?? 0),
                'ultima_actualizacion' => now(),
            ]);

            return $producto->refresh();
        });
    }

    /**
     * Actualiza un producto e inventario administrativo.
     *
     * @param array<string, mixed> $data
     * @return Producto
     */
    public function update(Producto $producto, array $data): Producto
    {
        return DB::transaction(function () use ($producto, $data): Producto {
            if (($data['visible_catalogo'] ?? false) && $producto->publicado_at === null) {
                $data['publicado_at'] = now();
            }

            if (($data['visible_catalogo'] ?? false) === false && $producto->publicado_at !== null) {
                $data['publicado_at'] = null;
            }

            $producto->update(collect($data)->except(['stock_actual', 'stock_minimo', 'stock_maximo'])->all());

            $producto->inventario()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => (int) $data['stock_actual'],
                    'stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                    'stock_maximo' => (int) ($data['stock_maximo'] ?? 0),
                    'ultima_actualizacion' => now(),
                ]
            );

            return $producto->refresh()->load(['vendor', 'categoria', 'inventario']);
        });
    }

    /**
     * Elimina logicamente un producto.
     */
    public function delete(Producto $producto): void
    {
        $producto->update([
            'is_active' => false,
            'visible_catalogo' => false,
            'publicado_at' => null,
        ]);

        $producto->delete();
    }
}
