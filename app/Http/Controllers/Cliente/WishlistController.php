<?php

namespace App\Http\Controllers\Cliente;

use App\DTOs\CarritoItemDTO;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Wishlist;
use App\Services\Carrito\CarritoService;
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
            'items' => Wishlist::query()
                ->with(['producto.vendor', 'producto.categoria', 'producto.inventario', 'producto.imagenPrincipal'])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * Alterna un producto dentro de la wishlist desde formularios clasicos.
     */
    public function toggle(Request $request, Producto $producto): RedirectResponse
    {
        $registro = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('producto_id', $producto->id)
            ->first();

        if ($registro !== null) {
            $registro->delete();

            return back()->with('success', 'Producto retirado de tu wishlist.');
        }

        Wishlist::query()->create([
            'user_id' => $request->user()->id,
            'producto_id' => $producto->id,
        ]);

        return back()->with('success', 'Producto agregado a tu wishlist.');
    }

    /**
     * Agrega todos los productos guardados al carrito.
     */
    public function addAllToCart(Request $request, CarritoService $carritoService): RedirectResponse
    {
        $items = Wishlist::query()
            ->with('producto.inventario')
            ->where('user_id', $request->user()->id)
            ->get();

        foreach ($items as $item) {
            if ($item->producto === null) {
                continue;
            }

            $carritoService->addItem($request, new CarritoItemDTO(
                productoId: $item->producto_id,
                cantidad: 1,
                precioUnitarioSnapshot: (float) ($item->producto->precio_oferta ?? $item->producto->precio_base)
            ));
        }

        return redirect()->route('cliente.carrito.index')->with('success', 'Tu wishlist se agrego al carrito.');
    }
}
