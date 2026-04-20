<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\DashboardRepartidorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del tablero del repartidor.
 */
class DashboardController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DashboardRepartidorService $dashboardRepartidorService)
    {
    }

    /**
     * Muestra resumen operativo del repartidor.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewCourierDashboard', $request->user());

        return view('repartidor.dashboard', ['metrics' => $this->dashboardRepartidorService->metrics($request->user())]);
    }
}
