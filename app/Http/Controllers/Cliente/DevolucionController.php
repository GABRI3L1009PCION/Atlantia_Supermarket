<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreDevolucionRequest;
use App\Models\Pedido;
use App\Services\Pedidos\DevolucionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador de devoluciones solicitadas por clientes.
 */
class DevolucionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DevolucionService $devolucionService)
    {
    }

    /**
     * Muestra formulario de solicitud de devolucion.
     */
    public function create(Pedido $pedido): View
    {
        $this->authorize('create', [\App\Models\Devolucion::class, $pedido]);

        return view('cliente.devoluciones.create', ['pedido' => $pedido->load(['items.producto', 'payments'])]);
    }

    /**
     * Guarda la solicitud de devolucion.
     */
    public function store(StoreDevolucionRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('create', [\App\Models\Devolucion::class, $pedido]);
        $this->devolucionService->solicitar($pedido, $request->user(), $request->validated());

        return redirect()
            ->route('cliente.pedidos.show', $pedido)
            ->with('success', 'Solicitud de devolucion enviada. Nuestro equipo la revisara pronto.');
    }
}
