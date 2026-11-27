<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\Catalogo\CatalogoService;
use App\Services\Monetizacion\PatrocinadosService;
use App\Services\Storefront\HeroBannerService;
use App\Support\WishlistStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function __construct(
        private readonly CatalogoService $catalogoService,
        private readonly HeroBannerService $heroBannerService,
    ) {}

    public function index(Request $request): View
    {
        $municipios = config('atlantia.marketplace.municipios', ['Puerto Barrios', 'Santo Tomas']);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'municipio' => ['nullable', Rule::in($municipios)],
            'orden' => ['nullable', Rule::in(['relevancia', 'recientes', 'precio_asc', 'precio_desc', 'nombre'])],
            'stock' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        /* Sin selector de ubicacion: por defecto se muestran todos los municipios operativos. */
        $municipio = $filters['municipio'] ?? null;
        $filters['municipio'] = $municipio;
        $filters['categoria_id'] = $filters['categoria_id'] ?? $filters['categoria'] ?? null;
        /* 24 productos por carga: filas completas con 2, 3, 4 o 6 columnas. */
        $filters['per_page'] = $filters['per_page'] ?? 24;

        $categories = Categoria::query()->active()->ordered()->get()->map(fn (Categoria $categoria): array => [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'image' => $this->categoryImageUrl($categoria),
            'href' => route('catalogo.index', [
                ...$request->only('q', 'orden'),
                'categoria_id' => $categoria->id,
                'municipio' => $municipio,
            ]).'#productos',
        ]);
        $banners = $this->heroBannerService->resolveCollectionForStorefront();

        return view('cliente.catalogo.index', [
            'catalogo' => $this->catalogoService->catalogo($filters),
            'categoriasDestacadas' => $categories,
            'municipioActivo' => $municipio,
            'zonaEntrega' => $municipio ?: implode(' y ', (array) $municipios),
            'filters' => $filters,
            'favoritos' => WishlistStore::ids($request, $request->user()),
            'heroBanners' => $banners,
            /* Solo en la primera pagina: los anuncios no se repiten al cargar mas productos. */
            'patrocinados' => (int) ($filters['page'] ?? 1) === 1
                ? app(PatrocinadosService::class)->paraResultados($filters['q'] ?? null, $filters['categoria_id'] ? (int) $filters['categoria_id'] : null)
                : collect(),
        ]);
    }

    private function categoryImageUrl(Categoria $categoria): ?string
    {
        if (! $categoria->imagen) {
            return null;
        }
        $path = trim((string) $categoria->imagen);
        if (Str::startsWith($path, ['http://', 'https://'])) {
            $urlPath = parse_url($path, PHP_URL_PATH);

            return is_string($urlPath) && Str::contains($urlPath, '/storage/') ? $urlPath : $path;
        }
        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return '/'.ltrim($path, '/');
        }
        $path = Str::after($path, 'public/');

        return Storage::disk('public')->exists($path) ? '/storage/'.ltrim($path, '/') : null;
    }
}
