<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Geolocalizacion\SeguimientoPedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Controlador de seguimiento de pedidos.
 */
class SeguimientoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly SeguimientoPedidoService $seguimientoPedidoService)
    {
    }

    /**
     * Muestra mapa y estado de seguimiento.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('track', $pedido);

        return view('cliente.seguimiento.show', ['seguimiento' => $this->seguimientoPedidoService->detail($pedido)]);
    }

    /**
     * Devuelve datos vivos para actualizar el mapa sin recargar la pagina.
     */
    public function live(Pedido $pedido): JsonResponse
    {
        $this->authorize('track', $pedido);
        $seguimiento = $this->seguimientoPedidoService->detail($pedido);

        return response()->json([
            'message' => 'Seguimiento actualizado.',
            'data' => [
                'pedido' => [
                    'uuid' => $pedido->uuid,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                ],
                'destino' => $seguimiento['destino'],
                'repartidor' => $seguimiento['repartidor'],
                'ruta_planificada' => $seguimiento['ruta_planificada'],
                'ruta_real' => $seguimiento['ruta_real'],
                'eta_minutos' => $seguimiento['eta_minutos'],
                'actualizado_at' => $seguimiento['actualizado_at'],
            ],
        ]);
    }
}
