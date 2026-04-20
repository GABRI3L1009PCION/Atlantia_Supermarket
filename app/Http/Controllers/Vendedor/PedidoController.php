<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\Pedido\UpdatePedidoEstadoRequest;
use App\Models\Pedido;
use App\Services\Pedidos\PedidoVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de pedidos recibidos por vendedor.
 */
class PedidoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PedidoVendedorService $pedidoVendedorService)
    {
    }

    /**
     * Lista pedidos recibidos.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnVendorOrders', Pedido::class);

        return view('vendedor.pedidos.index', ['pedidos' => $this->pedidoVendedorService->paginate($request->user())]);
    }

    /**
     * Muestra detalle de pedido recibido.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('viewVendorOrder', $pedido);

        return view('vendedor.pedidos.show', ['pedido' => $this->pedidoVendedorService->detail($pedido)]);
    }

    /**
     * Actualiza estado operativo del pedido.
     */
    public function updateEstado(UpdatePedidoEstadoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('updateVendorStatus', $pedido);
        $this->pedidoVendedorService->updateEstado($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Estado del pedido actualizado correctamente.');
    }
}
