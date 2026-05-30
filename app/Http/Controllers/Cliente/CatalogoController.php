<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Catalogo\CatalogoService;
use App\Services\Storefront\HeroBannerService;
use Illuminate\Http\Request;
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
    )
    {
    }

    /**
     * Muestra el catalogo navegable.
     */
    public function index(Request $request): View
    {
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
            ->map(function (Categoria $categoria): array {
                return [
                    'id' => $categoria->id,
                    'slug' => $categoria->slug,
                    'nombre' => $categoria->nombre,
                    'href' => route('catalogo.index', ['categoria' => $categoria->id]) . '#productos',
                    'image' => $this->categoryImageUrl($categoria),
                ];
            });

        return view('cliente.catalogo.index', [
            'catalogo' => $this->catalogoService->catalogo($request->all()),
            'destacados' => $destacados,
            'categoriasDestacadas' => $categoriasDestacadas,
            'heroBanners' => $this->heroBannerService->resolveCollectionForStorefront(),
            'heroBanner' => $this->heroBannerService->resolveForStorefront(),
            'metricas' => [
                'productos' => Producto::query()->publicados()->count(),
                'categorias' => Categoria::query()->active()->count(),
                'vendedores' => Vendor::query()->approved()->count(),
            ],
        ]);
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
}
