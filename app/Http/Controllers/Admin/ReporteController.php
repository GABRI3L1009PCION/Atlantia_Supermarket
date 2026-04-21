<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de reportes.
 */
class ReporteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReporteAdminService $reporteAdminService)
    {
    }

    /**
     * Muestra reportes consolidados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAdminReports', $request->user());

        return view('admin.reportes.index', [
            'reportes' => $this->reporteAdminService->summary($request->all()),
            'filters' => $request->only(['fecha_desde', 'fecha_hasta']),
        ]);
    }
}
