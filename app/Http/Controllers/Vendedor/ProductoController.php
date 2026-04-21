<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\StoreProductoRequest;
use App\Http\Requests\Vendedor\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\Catalogo\ProductoVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de productos propios del vendedor.
 */
class ProductoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ProductoVendedorService $productoVendedorService)
    {
    }

    /**
     * Lista productos del vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnProducts', Producto::class);

        return view('vendedor.productos.index', [
            'productos' => $this->productoVendedorService->paginate($request->user()),
            'categorias' => Categoria::query()->active()->ordered()->get(),
            'vendor' => $request->user()->vendor,
        ]);
    }

    /**
     * Guarda un producto.
     */
    public function store(StoreProductoRequest $request): RedirectResponse
    {
        $this->authorize('create', Producto::class);
        $this->productoVendedorService->create($request->user(), $request->validated());

        return back()->with('success', 'Producto creado correctamente.');
    }

    /**
     * Actualiza un producto propio.
     */
    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $producto);
        $this->productoVendedorService->update($producto, $request->validated());

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Elimina un producto propio.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $this->authorize('delete', $producto);
        $this->productoVendedorService->delete($producto);

        return back()->with('success', 'Producto eliminado correctamente.');
    }
}
