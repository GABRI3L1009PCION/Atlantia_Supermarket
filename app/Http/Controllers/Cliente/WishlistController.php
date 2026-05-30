<?php

namespace App\Http\Controllers\Cliente;

use App\DTOs\CarritoItemDTO;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\Carrito\CarritoService;
use App\Support\WishlistStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de wishlist del cliente.
 */
class WishlistController extends Controller
{
    /**
     * Muestra la lista de deseos.
     */
    public function index(Request $request): View
    {
        return view('cliente.wishlist.index', [
            'productos' => WishlistStore::paginateProducts($request, $request->user(), 20),
        ]);
    }

    /**
     * Alterna un producto dentro de la wishlist desde formularios clasicos.
     */
    public function toggle(Request $request, Producto $producto): RedirectResponse
    {
        $guardado = WishlistStore::toggle($request, $producto->id, $request->user());

        return back()->with('success', $guardado
            ? 'Producto agregado a tus favoritos.'
            : 'Producto retirado de tus favoritos.');
    }

    /**
     * Agrega todos los productos guardados al carrito.
     */
    public function addAllToCart(Request $request, CarritoService $carritoService): RedirectResponse
    {
        $productos = WishlistStore::products($request, $request->user());

        foreach ($productos as $producto) {
            if ($producto === null) {
                continue;
            }

            $carritoService->addItem($request, new CarritoItemDTO(
                productoId: $producto->id,
                cantidad: 1,
                precioUnitarioSnapshot: (float) ($producto->precio_oferta ?? $producto->precio_base)
            ));
        }

        return redirect()->route('cliente.carrito.index')->with('success', 'Tus favoritos se agregaron al carrito.');
    }
}
