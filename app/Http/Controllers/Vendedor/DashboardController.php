<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Vendedores\DashboardVendedorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del tablero del vendedor.
 */
class DashboardController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DashboardVendedorService $dashboardVendedorService)
    {
    }

    /**
     * Muestra metricas del vendedor autenticado.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewVendorDashboard', $request->user());

        return view('vendedor.dashboard', ['metrics' => $this->dashboardVendedorService->metrics($request->user())]);
    }
}
