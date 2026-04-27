<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteVendedorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de reportes para vendedor.
 */
class ReporteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReporteVendedorService $reporteVendedorService)
    {
    }

    /**
     * Muestra reportes del vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewVendorReports', $request->user());

        return view('vendedor.reportes.index', [
            'reportes' => $this->reporteVendedorService->summary($request->user(), $request->all()),
        ]);
    }
}
