<?php

namespace App\Http\Controllers\Cliente;

use App\DTOs\CarritoItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\Carrito\AddCarritoItemRequest;
use App\Http\Requests\Cliente\Carrito\UpdateCarritoItemRequest;
use App\Models\CarritoItem;
use App\Services\Carrito\CarritoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del carrito de compras.
 */
class CarritoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CarritoService $carritoService)
    {
    }

    /**
     * Muestra el carrito activo.
     */
    public function index(Request $request): View
    {
        return view('cliente.carrito.index', ['carrito' => $this->carritoService->current($request)]);
    }

    /**
     * Agrega un producto al carrito.
     */
    public function store(AddCarritoItemRequest $request): RedirectResponse
    {
        $this->carritoService->addItem($request, CarritoItemDTO::fromArray($request->validated()));

        return back()->with('success', 'Producto agregado al carrito.');
    }

    /**
     * Actualiza un item del carrito.
     */
    public function update(UpdateCarritoItemRequest $request, CarritoItem $item): RedirectResponse
    {
        abort_unless($this->carritoService->ownsItem($request, $item), 403);
        $payload = [...$request->validated(), 'producto_id' => $item->producto_id];
        $this->carritoService->updateItem($item, CarritoItemDTO::fromArray($payload));

        return back()->with('success', 'Carrito actualizado correctamente.');
    }

    /**
     * Elimina un item del carrito.
     */
    public function destroy(Request $request, CarritoItem $item): RedirectResponse
    {
        abort_unless($this->carritoService->ownsItem($request, $item), 403);
        $this->carritoService->removeItem($item, $request->user());

        return back()->with('success', 'Producto removido del carrito.');
    }
}
