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
    public function __construct(private readonly ProductoCatalogoService $productoCatalogoService)
    {
    }

    /**
     * Muestra el detalle publico de un producto.
     */
    public function show(Producto $producto): View
    {
        $this->authorize('viewCatalogo', $producto);

        return view('cliente.productos.show', ['producto' => $this->productoCatalogoService->detail($producto)]);
    }
}
