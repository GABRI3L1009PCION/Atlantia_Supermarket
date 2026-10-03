<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\Vendor;
use App\Services\Catalogo\CatalogoService;
use App\Services\Storefront\HeroBannerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controlador del catalogo publico para clientes.
 */
class CatalogoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(
        private readonly CatalogoService $catalogoService,
        private readonly HeroBannerService $heroBannerService,
    ) {}

    /**
     * Muestra el catalogo navegable.
     */
    public function index(Request $request): View
    {
        $municipioActivo = (string) $request->session()->get('cliente_municipio', 'Puerto Barrios');
        $destacados = Producto::query()
            ->with(['categoria', 'vendor', 'inventario', 'imagenPrincipal', 'media'])
            ->publicados()
            ->latest('publicado_at')
            ->take(5)
            ->get();

        $categoriasDestacadas = Categoria::query()
            ->active()
            ->ordered()
            ->get()
            ->map(function (Categoria $categoria) use ($municipioActivo): array {
                return [
                    'id' => $categoria->id,
                    'slug' => $categoria->slug,
                    'nombre' => $categoria->nombre,
                    'href' => route('comercios.index', array_filter([
                        'categoria' => $categoria->id,
                        'municipio' => $municipioActivo,
                    ])),
                    'image' => $this->categoryImageUrl($categoria),
                ];
            });
        $vendorsDestacados = Vendor::query()
            ->approved()
            ->withCount([
                'productos as productos_publicados_count' => fn (Builder $query) => $query->publicados(),
            ])
            ->withMin([
                'vendorDeliveryZones as tiempo_entrega_min' => fn (Builder $query) => $query->where('activa', true),
            ], 'tiempo_estimado_min')
            ->whereHas('productos', fn (Builder $query) => $query->publicados())
            ->when($municipioActivo !== '', fn (Builder $query) => $query->where('municipio', $municipioActivo))
            ->orderByDesc('productos_publicados_count')
            ->orderBy('business_name')
            ->take(6)
            ->get()
            ->map(function (Vendor $vendor): Vendor {
                $vendor->setAttribute('logo_url', $this->publicFileUrl($vendor->logo_path));
                $vendor->setAttribute('cover_url', $this->publicFileUrl($vendor->cover_path));

                return $vendor;
            });
        $carrito = $this->currentCartForRequest($request);
        $cartItems = $carrito?->items ?? collect();
        $cartSubtotal = (float) $cartItems->sum(
            fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad
        );

        return view('cliente.catalogo.index', [
            'catalogo' => $this->catalogoService->catalogo($request->all()),
            'destacados' => $destacados,
            'categoriasDestacadas' => $categoriasDestacadas,
            'vendorsDestacados' => $vendorsDestacados,
            'ratingsDestacados' => $this->ratingsForVendors($vendorsDestacados->pluck('id')),
            'municipioActivo' => $municipioActivo,
            'cartItemsCount' => (int) $cartItems->sum('cantidad'),
            'cartSubtotal' => $cartSubtotal,
            'heroBanners' => $this->heroBannerService->resolveCollectionForStorefront(),
            'heroBanner' => $this->heroBannerService->resolveForStorefront(),
            'metricas' => [
                'productos' => Producto::query()->publicados()->count(),
                'categorias' => Categoria::query()->active()->count(),
                'vendedores' => Vendor::query()->approved()->count(),
            ],
        ]);
    }

    private function currentCartForRequest(Request $request): ?Carrito
    {
        $query = Carrito::query()->active()->with('items');

        if ($request->user()) {
            return $query->where('user_id', $request->user()->id)->first();
        }

        return $query->where('session_id', $request->session()->getId())->first();
    }

    /**
     * Resuelve la imagen publica de la categoria sin depender del host de APP_URL.
     */
    private function categoryImageUrl(Categoria $categoria): ?string
    {
        if (! $categoria->imagen) {
            return null;
        }

        $path = trim((string) $categoria->imagen);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $storagePath = parse_url($path, PHP_URL_PATH);

            if (is_string($storagePath) && Str::contains($storagePath, '/storage/')) {
                return $storagePath;
            }

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
