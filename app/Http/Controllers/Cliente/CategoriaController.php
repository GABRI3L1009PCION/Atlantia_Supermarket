<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Muestra las categorias principales disponibles en el marketplace.
 */
class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $categorias = Categoria::query()
            ->active()
            ->root()
            ->ordered()
            ->withCount([
                'productos as productos_publicados_count' => fn (Builder $query) => $query->publicados(),
            ])
            ->with([
                'children' => fn ($query) => $query
                    ->active()
                    ->ordered()
                    ->withCount([
                        'productos as productos_publicados_count' => fn (Builder $productQuery) => $productQuery->publicados(),
                    ]),
            ])
            ->get()
            ->each(function (Categoria $categoria): void {
                $categoria->setAttribute(
                    'productos_publicados_total',
                    (int) $categoria->productos_publicados_count
                        + (int) $categoria->children->sum('productos_publicados_count')
                );
            });

        return view('cliente.categorias.index', [
            'categorias' => $categorias,
            'municipioActivo' => (string) $request->session()->get('cliente_municipio', 'Puerto Barrios'),
        ]);
    }
}
