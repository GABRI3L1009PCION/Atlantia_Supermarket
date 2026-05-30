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
    private const PER_PAGE_OPTIONS = [8, 12, 24, 48];

    /**
     * Pagina productos globales.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = $this->resolvePerPage($filters['per_page'] ?? null);

        return Producto::query()
            ->with(['vendor', 'categoria', 'inventario', 'imagenPrincipal', 'media'])
            ->when($filters['categoria_id'] ?? null, fn ($query, $categoriaId) => $query->where('categoria_id', $categoriaId))
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->when($filters['estado'] ?? null, function ($query, string $estado): void {
                match ($estado) {
                    'activo' => $query->where('is_active', true),
                    'inactivo' => $query->where('is_active', false),
                    default => null,
                };
            })
            ->when($filters['stock'] ?? null, function ($query, string $stock): void {
                match ($stock) {
                    'agotado' => $query->whereHas('inventario', fn ($builder) => $builder->where('stock_actual', '<=', 0)),
                    'bajo' => $query->whereHas('inventario', fn ($builder) => $builder->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_actual', '>', 0)),
                    'disponible' => $query->whereHas('inventario', fn ($builder) => $builder->where('stock_actual', '>', 0)),
                    default => null,
                };
            })
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(fn ($builder) => $builder
                    ->where('nombre', 'like', '%' . $q . '%')
                    ->orWhere('sku', 'like', '%' . $q . '%')
                    ->orWhere('codigo_barras', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%'));
            })
            ->when(
                ($filters['orden'] ?? 'recientes') === 'nombre',
                fn ($query) => $query->orderBy('nombre'),
                fn ($query) => match ($filters['orden'] ?? 'recientes') {
                    'precio_asc' => $query->orderByRaw('COALESCE(precio_oferta, precio_base) asc'),
                    'precio_desc' => $query->orderByRaw('COALESCE(precio_oferta, precio_base) desc'),
                    'stock_asc' => $query
                        ->leftJoin('inventarios', 'inventarios.producto_id', '=', 'productos.id')
                        ->select('productos.*')
                        ->orderBy('inventarios.stock_actual'),
                    default => $query->latest('productos.created_at'),
                }
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    private function resolvePerPage(mixed $value): int
    {
        $perPage = is_scalar($value) ? (int) $value : 12;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 12;
    }

    /**
     * Devuelve detalle administrativo.
     */
    public function detail(Producto $producto): Producto
    {
        return $producto->load(['vendor.fiscalProfile', 'categoria', 'inventario', 'imagenes', 'media', 'resenas']);
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
            $sku = $this->uniqueSku((string) $data['nombre'], $vendor->id, $data['sku'] ?? null);

            $producto = Producto::query()->create([
                ...collect($data)->only([
                    'categoria_id',
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
                'sku' => $sku,
                'codigo_barras' => $this->uniqueBarcode(),
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
            $sku = $this->uniqueSku(
                (string) $data['nombre'],
                $vendor->id,
                $data['sku'] ?? $producto->sku,
                $producto->id
            );

            if (($data['visible_catalogo'] ?? false) && $producto->publicado_at === null) {
                $data['publicado_at'] = now();
            }

            if (($data['visible_catalogo'] ?? false) === false && $producto->publicado_at !== null) {
                $data['publicado_at'] = null;
            }

            $producto->update([
                ...collect($data)->except(['owner_type', 'imagenes', 'stock_actual', 'stock_minimo', 'stock_maximo'])->all(),
                'vendor_id' => $vendor->id,
                'sku' => $sku,
                'codigo_barras' => $producto->codigo_barras ?: $this->uniqueBarcode(),
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
     * Genera un SKU legible desde el nombre y lo mantiene unico por vendedor.
     */
    private function uniqueSku(string $nombre, int $vendorId, ?string $preferredSku = null, ?int $ignoreProductId = null): string
    {
        $base = $preferredSku ?: $nombre;
        $base = Str::upper(Str::slug($base, '-')) ?: 'PRODUCTO';
        $base = Str::limit($base, 56, '');
        $sku = $base;
        $suffix = 1;

        while ($this->skuExists($sku, $vendorId, $ignoreProductId)) {
            $suffix += 1;
            $sku = Str::limit($base, 56, '') . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
        }

        return $sku;
    }

    private function skuExists(string $sku, int $vendorId, ?int $ignoreProductId = null): bool
    {
        return Producto::withTrashed()
            ->where('vendor_id', $vendorId)
            ->where('sku', $sku)
            ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
            ->exists();
    }

    /**
     * Genera un codigo EAN-13 interno, unico a nivel de catalogo.
     */
    private function uniqueBarcode(): string
    {
        do {
            $body = '740' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $barcode = $body . $this->ean13CheckDigit($body);
        } while (Producto::withTrashed()->where('codigo_barras', $barcode)->exists());

        return $barcode;
    }

    private function ean13CheckDigit(string $body): int
    {
        $sum = 0;

        foreach (str_split($body) as $index => $digit) {
            $sum += ((int) $digit) * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
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

            $producto
                ->addMediaFromDisk($path, $disk)
                ->preservingOriginal()
                ->usingName($producto->nombre)
                ->usingFileName(basename($path))
                ->toMediaCollection('productos', $disk);

            $producto->imagenes()->create([
                'path' => $path,
                'alt_text' => $producto->nombre,
                'orden' => $startOrder + $index,
                'es_principal' => ! $hasPrincipal && $index === 0,
            ]);
        }
    }
}
