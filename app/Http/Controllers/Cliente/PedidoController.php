<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Pedidos\PedidoClienteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de pedidos del cliente.
 */
class PedidoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PedidoClienteService $pedidoClienteService)
    {
    }

    /**
     * Lista pedidos del cliente autenticado.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnOrders', Pedido::class);

        return view('cliente.pedidos.index', ['pedidos' => $this->pedidoClienteService->paginate($request->user())]);
    }

    /**
     * Muestra el detalle de un pedido.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('view', $pedido);

        return view('cliente.pedidos.show', ['pedido' => $this->pedidoClienteService->detail($pedido)]);
    }

    /**
     * Muestra la confirmacion de un pedido creado como invitado en esta sesion.
     */
    public function guestShow(Request $request, Pedido $pedido): View
    {
        abort_unless(
            in_array($pedido->uuid, $request->session()->get('guest_order_uuids', []), true),
            403
        );

        return view('cliente.pedidos.show', [
            'pedido' => $this->pedidoClienteService->detail($pedido),
            'guestContactEmail' => $request->session()->get("guest_order_email.{$pedido->uuid}"),
        ]);
    }
}
