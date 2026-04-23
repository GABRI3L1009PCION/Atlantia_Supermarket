<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Catalogo\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del catalogo publico para clientes.
 */
class CatalogoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CatalogoService $catalogoService)
    {
    }

    /**
     * Muestra el catalogo navegable.
     */
    public function index(Request $request): View
    {
        $imagenesCategoria = [
            'frutas-y-verduras' => 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=600&q=80',
            'carnes-y-aves' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=600&q=80',
            'abarrotes-secos' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80',
            'panaderia' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80',
            'lacteos' => 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?auto=format&fit=crop&w=600&q=80',
            'bebidas' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=600&q=80',
        ];

        $destacados = Producto::query()
            ->with(['categoria', 'vendor', 'inventario', 'imagenPrincipal', 'media'])
            ->publicados()
            ->latest('publicado_at')
            ->take(5)
            ->get();

        $categoriasDestacadas = Categoria::query()
            ->active()
            ->ordered()
            ->take(6)
            ->get()
            ->map(function (Categoria $categoria) use ($imagenesCategoria): array {
                $fallback = $imagenesCategoria[$categoria->slug] ?? 'https://images.unsplash.com/photo-1516594798947-e65505dbb29d?auto=format&fit=crop&w=600&q=80';

                return [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre,
                    'href' => route('catalogo.index', ['categoria' => $categoria->id]) . '#productos',
                    'image' => $fallback,
                ];
            });

        return view('cliente.catalogo.index', [
            'catalogo' => $this->catalogoService->catalogo($request->all()),
            'destacados' => $destacados,
            'categoriasDestacadas' => $categoriasDestacadas,
            'metricas' => [
                'productos' => Producto::query()->publicados()->count(),
                'categorias' => Categoria::query()->active()->count(),
                'vendedores' => Vendor::query()->approved()->count(),
            ],
        ]);
    }
}
