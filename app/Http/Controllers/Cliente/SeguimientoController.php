<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Geolocalizacion\SeguimientoPedidoService;
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
}
