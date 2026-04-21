<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dte\AnularDteRequest;
use App\Models\Dte\DteFactura;
use App\Models\Vendor;
use App\Services\Fel\DteAnulacionService;
use App\Services\Fel\ReporteFiscalService;
use Illuminate\Http\RedirectResponse;
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
    public function __construct(
        private readonly ReporteFiscalService $reporteFiscalService,
        private readonly DteAnulacionService $dteAnulacionService
    ) {
    }

    /**
     * Lista facturas DTE.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DteFactura::class);

        return view('admin.dte.index', [
            'dtes' => $this->reporteFiscalService->paginateGlobal($request->all()),
            'dashboard' => $this->reporteFiscalService->dashboard($request->all()),
            'vendors' => Vendor::query()->approved()->orderBy('business_name')->get(),
        ]);
    }

    /**
     * Muestra detalle fiscal de un DTE.
     */
    public function show(DteFactura $dte): View
    {
        $this->authorize('view', $dte);

        return view('admin.dte.show', ['dte' => $this->reporteFiscalService->detail($dte)]);
    }

    /**
     * Reintenta certificacion para un DTE.
     */
    public function retry(DteFactura $dte): RedirectResponse
    {
        $this->authorize('view', $dte);
        $this->reporteFiscalService->reintentar($dte);

        return back()->with('success', 'Se reintento la certificacion del DTE.');
    }

    /**
     * Solicita anulacion administrativa del DTE.
     */
    public function anular(AnularDteRequest $request, DteFactura $dte): RedirectResponse
    {
        $this->authorize('view', $dte);
        $this->dteAnulacionService->anular($dte, $request->validated(), $request->user());

        return back()->with('success', 'La anulacion del DTE fue procesada correctamente.');
    }
}
