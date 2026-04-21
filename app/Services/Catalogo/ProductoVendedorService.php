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
    public function paginate(User $user): LengthAwarePaginator
    {
        return Producto::query()
            ->with(['categoria', 'inventario', 'imagenPrincipal'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->paginate(20);
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

        $producto->update($data);

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

            $producto->imagenes()->create([
                'path' => $path,
                'alt_text' => $producto->nombre,
                'orden' => $index,
                'es_principal' => $index === 0,
            ]);
        }
    }
}
