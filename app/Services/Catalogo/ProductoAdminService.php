<?php

namespace App\Services\Catalogo;

use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            $vendor = $this->resolveProductOwner($data);

            $producto = Producto::query()->create([
                ...collect($data)->only([
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
                'vendor_id' => $vendor->id,
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

            $this->storeImages($producto, $data['imagenes'] ?? []);

            return $producto->refresh()->load(['vendor', 'categoria', 'inventario', 'imagenes']);
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
            $vendor = $this->resolveProductOwner($data);

            if (($data['visible_catalogo'] ?? false) && $producto->publicado_at === null) {
                $data['publicado_at'] = now();
            }

            if (($data['visible_catalogo'] ?? false) === false && $producto->publicado_at !== null) {
                $data['publicado_at'] = null;
            }

            $producto->update([
                ...collect($data)->except(['owner_type', 'imagenes', 'stock_actual', 'stock_minimo', 'stock_maximo'])->all(),
                'vendor_id' => $vendor->id,
            ]);

            $producto->inventario()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => (int) $data['stock_actual'],
                    'stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                    'stock_maximo' => (int) ($data['stock_maximo'] ?? 0),
                    'ultima_actualizacion' => now(),
                ]
            );

            $this->storeImages($producto, $data['imagenes'] ?? []);

            return $producto->refresh()->load(['vendor', 'categoria', 'inventario', 'imagenes']);
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

    /**
     * Resuelve si el producto pertenece a Atlantia o a un vendedor externo.
     *
     * @param array<string, mixed> $data
     * @return Vendor
     */
    private function resolveProductOwner(array $data): Vendor
    {
        if (($data['owner_type'] ?? 'vendor') === 'vendor') {
            return Vendor::query()->approved()->findOrFail((int) $data['vendor_id']);
        }

        return $this->atlantiaVendor();
    }

    /**
     * Crea o recupera el vendedor interno que representa inventario propio de Atlantia.
     */
    private function atlantiaVendor(): Vendor
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'inventario@atlantia.local'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Inventario Atlantia Supermarket',
                'password' => Hash::make(Str::random(48)),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_system_user' => true,
                'two_factor_enabled' => false,
            ]
        );

        $vendor = Vendor::query()->firstOrCreate(
            ['slug' => 'atlantia-supermarket'],
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'business_name' => 'Atlantia Supermarket',
                'descripcion' => 'Inventario propio vendido directamente por Atlantia Supermarket.',
                'telefono_publico' => config('atlantia.contact.phone'),
                'email_publico' => config('atlantia.contact.email', 'contacto@atlantia.local'),
                'municipio' => 'Puerto Barrios',
                'direccion_comercial' => 'Puerto Barrios, Izabal, Guatemala',
                'is_approved' => true,
                'approved_at' => now(),
                'status' => 'approved',
                'commission_percentage' => 0,
                'monthly_rent' => 0,
                'accepts_cash' => true,
                'accepts_transfer' => true,
                'accepts_card' => true,
            ]
        );

        VendorFiscalProfile::query()->firstOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'nit' => 'CF-ATLANTIA',
                'razon_social' => 'Atlantia Supermarket',
                'nombre_comercial_sat' => 'Atlantia Supermarket',
                'direccion_fiscal' => 'Puerto Barrios, Izabal, Guatemala',
                'regimen_sat' => 'general',
                'codigo_establecimiento' => 'ATL-001',
                'afiliacion_iva' => 'GEN',
                'certificador_fel' => 'infile',
                'fel_activo' => false,
            ]
        );

        return $vendor;
    }

    /**
     * Guarda imagenes administrativas del producto.
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
        $startOrder = (int) $producto->imagenes()->max('orden') + 1;
        $hasPrincipal = $producto->imagenes()->where('es_principal', true)->exists();

        foreach ($imagenes as $index => $imagen) {
            $path = $imagen->store('productos/' . $producto->uuid, $disk);

            $producto->imagenes()->create([
                'path' => $path,
                'alt_text' => $producto->nombre,
                'orden' => $startOrder + $index,
                'es_principal' => ! $hasPrincipal && $index === 0,
            ]);
        }
    }
}
