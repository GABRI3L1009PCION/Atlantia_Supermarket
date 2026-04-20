<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del tablero administrativo.
 */
class DashboardController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * Muestra metricas consolidadas de Atlantia.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAdminDashboard', $request->user());

        return view('admin.dashboard', ['metrics' => $this->dashboardService->metrics($request->user())]);
    }
}
