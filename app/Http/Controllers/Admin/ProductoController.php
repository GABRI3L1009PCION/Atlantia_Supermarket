<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Producto\ModerateProductoRequest;
use App\Models\Producto;
use App\Services\Catalogo\ProductoAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de productos.
 */
class ProductoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ProductoAdminService $productoAdminService)
    {
    }

    /**
     * Lista productos del marketplace.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Producto::class);

        return view('admin.productos.index', ['productos' => $this->productoAdminService->paginate($request->all())]);
    }

    /**
     * Muestra el detalle administrativo de un producto.
     */
    public function show(Producto $producto): View
    {
        $this->authorize('view', $producto);

        return view('admin.productos.show', ['producto' => $this->productoAdminService->detail($producto)]);
    }

    /**
     * Modera visibilidad o estado del producto.
     */
    public function moderate(ModerateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->authorize('moderate', $producto);
        $this->productoAdminService->moderate($producto, $request->validated(), $request->user());

        return back()->with('success', 'Producto actualizado correctamente.');
    }
}
