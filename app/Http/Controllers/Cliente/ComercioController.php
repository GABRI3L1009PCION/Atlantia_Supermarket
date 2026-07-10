<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Storefront publico de comercios dentro del marketplace Atlantia.
 */
class ComercioController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $municipio = trim((string) $request->query('municipio', $request->session()->get('cliente_municipio', '')));
        $categoriaId = $request->integer('categoria') ?: null;

        $vendors = Vendor::query()
            ->approved()
            ->withCount([
                'productos as productos_publicados_count' => fn (Builder $query) => $query->publicados(),
            ])
            ->whereHas('productos', fn (Builder $query) => $query->publicados())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('business_name', 'like', "%{$search}%")
                        ->orWhere('business_category', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('productos', function (Builder $productQuery) use ($search): void {
                            $productQuery
                                ->publicados()
                                ->where(function (Builder $productNested) use ($search): void {
                                    $productNested
                                        ->where('nombre', 'like', "%{$search}%")
                                        ->orWhere('descripcion', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($municipio !== '', fn (Builder $query) => $query->where('municipio', $municipio))
            ->when($categoriaId !== null, function (Builder $query) use ($categoriaId): void {
                $query->whereHas('productos', fn (Builder $productQuery) => $productQuery
                    ->publicados()
                    ->where('categoria_id', $categoriaId));
            })
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString();
        $vendors->getCollection()->transform(function (Vendor $vendor): Vendor {
            $vendor->setAttribute('logo_url', $this->publicFileUrl($vendor->logo_path));
            $vendor->setAttribute('cover_url', $this->publicFileUrl($vendor->cover_path));

            return $vendor;
        });

        return view('cliente.comercios.index', [
            'vendors' => $vendors,
            'ratings' => $this->ratingsForVendors($vendors->getCollection()->pluck('id')),
            'categorias' => Categoria::query()->active()->ordered()->get(),
            'municipios' => Vendor::query()
                ->approved()
                ->whereNotNull('municipio')
                ->distinct()
                ->orderBy('municipio')
                ->pluck('municipio'),
            'filters' => [
                'q' => $search,
                'municipio' => $municipio,
                'categoria' => $categoriaId,
            ],
        ]);
    }

    public function show(Request $request, Vendor $vendor): View
    {
        abort_unless($vendor->is_approved && $vendor->status === 'approved', 404);

        $vendor->load(['vendorDeliveryZones' => fn ($query) => $query->where('activa', true)]);

        $sort = (string) $request->query('orden', 'relevancia');
        $sort = in_array($sort, ['relevancia', 'precio_asc', 'precio_desc', 'nombre'], true)
            ? $sort
            : 'relevancia';
        $products = Producto::query()
            ->with(['categoria', 'inventario', 'imagenPrincipal', 'media'])
            ->withAvg(['resenas as rating_promedio' => fn (Builder $query) => $query->aprobadas()], 'calificacion')
            ->publicados()
            ->where('vendor_id', $vendor->id)
            ->orderBy('categoria_id')
            ->when($sort === 'precio_asc', fn (Builder $query) => $query->orderByRaw('COALESCE(precio_oferta, precio_base) ASC'))
            ->when($sort === 'precio_desc', fn (Builder $query) => $query->orderByRaw('COALESCE(precio_oferta, precio_base) DESC'))
            ->when($sort === 'nombre', fn (Builder $query) => $query->orderBy('nombre'))
            ->when(! in_array($sort, ['precio_asc', 'precio_desc', 'nombre'], true), fn (Builder $query) => $query->orderByDesc('publicado_at')->orderBy('nombre'))
            ->get();

        $ratings = $this->ratingsForVendors(collect([$vendor->id]));
        $rating = $ratings->get($vendor->id, ['rating' => null, 'total' => 0]);
        $deliveryFee = $vendor->vendorDeliveryZones->min('costo_override');
        $estimatedTime = $vendor->vendorDeliveryZones->min('tiempo_estimado_min');
        $carrito = $this->currentCartForRequest($request);
        $cartItems = $carrito?->items
            ->filter(fn ($item): bool => (int) ($item->producto?->vendor_id ?? 0) === (int) $vendor->id)
            ->values() ?? collect();

        return view('cliente.comercios.show', [
            'vendor' => $vendor,
            'products' => $products,
            'productsByCategory' => $products->groupBy(fn (Producto $producto): string => $producto->categoria?->nombre ?? 'General'),
            'rating' => $rating,
            'deliveryFee' => $deliveryFee === null ? 10.00 : (float) $deliveryFee,
            'estimatedTime' => $estimatedTime === null ? 30 : (int) $estimatedTime,
            'minimumOrder' => 25 + (($vendor->id % 3) * 5),
            'distanceKm' => number_format(0.8 + (($vendor->id % 7) * 0.35), 1),
            'sort' => $sort,
            'cartItems' => $cartItems,
            'logoUrl' => $this->publicFileUrl($vendor->logo_path),
            'coverUrl' => $this->publicFileUrl($vendor->cover_path),
        ]);
    }

    private function currentCartForRequest(Request $request): ?Carrito
    {
        $query = Carrito::query()
            ->active()
            ->with([
                'items.producto.imagenPrincipal',
                'items.producto.media',
                'items.producto.vendor',
            ]);

        if ($request->user()) {
            return $query->where('user_id', $request->user()->id)->first();
        }

        return $query->where('session_id', $request->session()->getId())->first();
    }

    /**
     * @param  Collection<int, int>  $vendorIds
     * @return Collection<int, array{rating: float|null, total: int}>
     */
    private function ratingsForVendors(Collection $vendorIds): Collection
    {
        if ($vendorIds->isEmpty()) {
            return collect();
        }

        return Resena::query()
            ->selectRaw('productos.vendor_id, AVG(resenas.calificacion) as rating, COUNT(*) as total')
            ->join('productos', 'productos.id', '=', 'resenas.producto_id')
            ->whereIn('productos.vendor_id', $vendorIds->unique()->values())
            ->where('resenas.aprobada', true)
            ->groupBy('productos.vendor_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                (int) $row->vendor_id => [
                    'rating' => $row->rating === null ? null : round((float) $row->rating, 1),
                    'total' => (int) $row->total,
                ],
            ]);
    }

    private function publicFileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return '/'.ltrim($path, '/');
        }

        $path = Str::after($path, 'public/');

        if (Storage::disk('public')->exists($path)) {
            return '/storage/'.ltrim($path, '/');
        }

        return null;
    }
}
