<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dte\DteFactura;
use App\Services\Fel\ReporteFiscalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de DTE.
 */
class DteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReporteFiscalService $reporteFiscalService)
    {
    }

    /**
     * Lista facturas DTE.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DteFactura::class);

        return view('admin.dte.index', ['dtes' => $this->reporteFiscalService->paginateGlobal($request->all())]);
    }

    /**
     * Muestra detalle fiscal de un DTE.
     */
    public function show(DteFactura $dte): View
    {
        $this->authorize('view', $dte);

        return view('admin.dte.show', ['dte' => $this->reporteFiscalService->detail($dte)]);
    }
}
