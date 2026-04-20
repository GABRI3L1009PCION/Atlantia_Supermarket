<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\Ruta\CompletarRutaRequest;
use App\Models\DeliveryRoute;
use App\Services\Geolocalizacion\RutaOptimaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de rutas del repartidor.
 */
class RutaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RutaOptimaService $rutaOptimaService)
    {
    }

    /**
     * Lista rutas del repartidor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAssignedRoutes', DeliveryRoute::class);

        return view('repartidor.rutas.index', ['rutas' => $this->rutaOptimaService->assignedTo($request->user())]);
    }

    /**
     * Muestra una ruta asignada.
     */
    public function show(DeliveryRoute $route): View
    {
        $this->authorize('view', $route);

        return view('repartidor.rutas.show', ['ruta' => $this->rutaOptimaService->detail($route)]);
    }

    /**
     * Completa una ruta con evidencia.
     */
    public function complete(CompletarRutaRequest $request, DeliveryRoute $route): RedirectResponse
    {
        $this->authorize('complete', $route);
        $this->rutaOptimaService->complete($route, $request->validated(), $request->user());

        return back()->with('success', 'Ruta completada correctamente.');
    }
}
