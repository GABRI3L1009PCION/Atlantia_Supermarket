<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Producto\ModerateProductoRequest;
use App\Http\Requests\Admin\Producto\StoreProductoRequest;
use App\Http\Requests\Admin\Producto\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
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

        return view('admin.productos.index', [
            'productos' => $this->productoAdminService->paginate($request->all()),
            'categorias' => Categoria::query()->where('is_active', true)->orderBy('nombre')->get(),
            'vendors' => Vendor::query()->approved()->orderBy('business_name')->get(),
        ]);
    }

    /**
     * Crea un producto administrativo.
     */
    public function store(StoreProductoRequest $request): RedirectResponse
    {
        $this->authorize('create', Producto::class);
        $this->productoAdminService->create($request->validated());

        return back()->with('success', 'Producto creado correctamente.');
    }

    /**
     * Muestra el detalle administrativo de un producto.
     */
    public function show(Producto $producto): View
    {
        $this->authorize('view', $producto);

        return view('admin.productos.show', [
            'producto' => $this->productoAdminService->detail($producto),
            'categorias' => Categoria::query()->where('is_active', true)->orderBy('nombre')->get(),
            'vendors' => Vendor::query()->approved()->orderBy('business_name')->get(),
        ]);
    }

    /**
     * Actualiza un producto administrativo.
     */
    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $producto);
        $this->productoAdminService->update($producto, $request->validated());

        return back()->with('success', 'Producto actualizado correctamente.');
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

    /**
     * Elimina un producto del catalogo.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $this->authorize('delete', $producto);
        $this->productoAdminService->delete($producto);

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }
}
