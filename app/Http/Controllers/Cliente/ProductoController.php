<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\Catalogo\ProductoCatalogoService;
use Illuminate\View\View;

/**
 * Controlador de detalle de producto para clientes.
 */
class ProductoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ProductoCatalogoService $productoCatalogoService) {}

    /**
     * Muestra el detalle publico de un producto.
     */
    public function show(Producto $producto): View
    {
        $this->authorize('viewCatalogo', $producto);

        $publicadas = $producto->resenas()->aprobadas();
        $total = (clone $publicadas)->count();
        $distribucion = [];
        foreach ([5, 4, 3, 2, 1] as $estrellas) {
            $distribucion[$estrellas] = (clone $publicadas)->where('calificacion', $estrellas)->count();
        }

        return view('cliente.productos.show', [
            'producto' => $this->productoCatalogoService->detail($producto),
            'opiniones' => [
                'total' => $total,
                'promedio' => $total > 0 ? round((float) (clone $publicadas)->avg('calificacion'), 1) : null,
                'distribucion' => $distribucion,
                'lista' => (clone $publicadas)->with('cliente:id,name')->latest()->limit(10)->get(),
            ],
        ]);
    }
}
