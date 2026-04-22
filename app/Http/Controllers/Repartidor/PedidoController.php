<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\Pedido\UpdateEntregaEstadoRequest;
use App\Models\Pedido;
use App\Services\Pedidos\PedidoRepartidorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de pedidos asignados al repartidor.
 */
class PedidoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PedidoRepartidorService $pedidoRepartidorService)
    {
    }

    /**
     * Lista pedidos asignados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAssignedOrders', Pedido::class);

        return view('repartidor.pedidos.index', ['pedidos' => $this->pedidoRepartidorService->assigned($request->user())]);
    }

    /**
     * Muestra detalle de pedido asignado.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('viewAssigned', $pedido);

        return view('repartidor.pedidos.show', ['pedido' => $this->pedidoRepartidorService->detail($pedido)]);
    }

    /**
     * Actualiza estado de entrega.
     */
    public function updateEstado(UpdateEntregaEstadoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $this->pedidoRepartidorService->updateEstado($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Estado de entrega actualizado correctamente.');
    }

    /**
     * Acepta una entrega asignada.
     */
    public function accept(Pedido $pedido, Request $request): RedirectResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $this->pedidoRepartidorService->accept($pedido, $request->user());

        return back()->with('success', 'Entrega aceptada. Te avisaremos cuando este lista para recoger.');
    }

    /**
     * Marca el pedido como recogido.
     */
    public function pickup(Pedido $pedido, Request $request): RedirectResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $this->pedidoRepartidorService->pickup($pedido, $request->user());

        return back()->with('success', 'Pedido recogido. Ya puedes iniciar la entrega al cliente.');
    }

    /**
     * Marca el pedido como entregado.
     */
    public function deliver(UpdateEntregaEstadoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $this->pedidoRepartidorService->deliver($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Entrega completada correctamente.');
    }
}
