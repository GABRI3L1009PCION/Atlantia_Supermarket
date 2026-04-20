<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\Inventario\UpdateInventarioRequest;
use App\Models\Inventario;
use App\Models\Producto;
use App\Services\Inventario\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de inventario del vendedor.
 */
class InventarioController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Lista inventario del vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnInventory', Inventario::class);

        return view('vendedor.inventario.index', ['inventario' => $this->stockService->forVendor($request->user())]);
    }

    /**
     * Actualiza inventario de un producto propio.
     */
    public function update(UpdateInventarioRequest $request, Producto $producto): RedirectResponse
    {
        $this->authorize('updateInventory', $producto);
        $this->stockService->updateForProduct($producto, $request->validated(), $request->user());

        return back()->with('success', 'Inventario actualizado correctamente.');
    }
}
