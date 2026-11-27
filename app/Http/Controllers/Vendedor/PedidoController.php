<?php

namespace App\Http\Controllers\Vendedor;

use App\Exceptions\PagoRechazadoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\Pedido\UpdatePedidoEstadoRequest;
use App\Models\Pedido;
use App\Services\Pedidos\PedidoVendedorService;
use Illuminate\Http\JsonResponse;
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
    public function __construct(private readonly PedidoVendedorService $pedidoVendedorService) {}

    /**
     * Lista pedidos recibidos.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnVendorOrders', Pedido::class);

        return view('vendedor.pedidos.index', [
            'pedidos' => $this->pedidoVendedorService->paginate($request->user(), $request->all()),
            'dashboard' => $this->pedidoVendedorService->dashboard($request->user()),
        ]);
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
     * Ultimo pedido recibido, para la alerta sonora del panel.
     */
    public function nuevos(Request $request): JsonResponse
    {
        $this->authorize('viewOwnVendorOrders', Pedido::class);
        $vendor = $request->user()->vendor;

        $ultimo = Pedido::query()
            ->where('vendor_id', $vendor?->id)
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->latest('id')
            ->first(['id', 'uuid', 'numero_pedido', 'total', 'created_at']);

        return response()->json([
            'alerta_sonora' => (bool) ($vendor?->alerta_sonora_pedidos ?? true),
            'ultimo' => $ultimo === null ? null : [
                'id' => $ultimo->id,
                'numero' => $ultimo->numero_pedido,
                'total' => (float) $ultimo->total,
                'url' => route('vendedor.pedidos.show', $ultimo),
                'comanda' => route('vendedor.pedidos.comanda', $ultimo),
            ],
        ]);
    }

    /**
     * Comanda imprimible del pedido (ticket de preparacion).
     */
    public function comanda(Pedido $pedido): View
    {
        $this->authorize('viewVendorOrder', $pedido);

        return view('vendedor.pedidos.comanda', ['pedido' => $pedido->load(['items', 'direccion'])]);
    }

    /**
     * Actualiza estado operativo del pedido.
     */
    public function updateEstado(UpdatePedidoEstadoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('updateVendorStatus', $pedido);
        try {
            $pedido = $this->pedidoVendedorService->updateEstado($pedido, $request->validated(), $request->user());
        } catch (PagoRechazadoException $exception) {
            report($exception);

            return back()->with('error', 'No pudimos devolver el pago con tarjeta del cliente, así que el pedido no se canceló. Intenta de nuevo en unos minutos.');
        }

        return back()->with('success', $pedido->estadoValor() === 'cancelado'
            ? 'Pedido cancelado. Los productos volvieron a tu inventario y avisamos al cliente.'
            : 'Estado del pedido actualizado correctamente.');
    }
}
