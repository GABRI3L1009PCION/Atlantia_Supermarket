<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\Incidencia\StoreIncidenciaRequest;
use App\Models\Pedido;
use App\Services\Repartidores\IncidenciaService;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de incidencias de entrega.
 */
class IncidenciaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly IncidenciaService $incidenciaService)
    {
    }

    /**
     * Registra una incidencia sobre un pedido asignado.
     */
    public function store(StoreIncidenciaRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('reportIncident', $pedido);
        $this->incidenciaService->store($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Incidencia registrada correctamente.');
    }
}
