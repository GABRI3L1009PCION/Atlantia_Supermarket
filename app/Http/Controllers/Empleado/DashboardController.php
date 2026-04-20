<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Services\Empleados\DashboardEmpleadoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del tablero del empleado Atlantia.
 */
class DashboardController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DashboardEmpleadoService $dashboardEmpleadoService)
    {
    }

    /**
     * Muestra resumen operativo del empleado.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewEmployeeDashboard', $request->user());

        return view('empleado.dashboard', ['metrics' => $this->dashboardEmpleadoService->metrics($request->user())]);
    }
}
