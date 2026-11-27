<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use App\Services\Admin\NegocioDashboardService;
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
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly NegocioDashboardService $negocio,
    ) {}

    /**
     * Muestra metricas consolidadas de Atlantia.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAdminDashboard', $request->user());

        if ($request->user()->isSuperAdmin()) {
            return view('super-admin.dashboard', [
                'negocio' => $this->negocio->resumen(),
            ]);
        }

        return view('admin.dashboard', ['metrics' => $this->dashboardService->metrics($request->user())]);
    }
}
