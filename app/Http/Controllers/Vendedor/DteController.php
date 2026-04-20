<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\Dte\AnularDteRequest;
use App\Models\Dte\DteFactura;
use App\Services\Fel\DteAnulacionService;
use App\Services\Fel\ReporteFiscalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de DTE propios del vendedor.
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
     * Lista DTE propios.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnDtes', DteFactura::class);

        return view('vendedor.dte.index', ['dtes' => $this->reporteFiscalService->paginateForVendor($request->user())]);
    }

    /**
     * Muestra detalle de DTE propio.
     */
    public function show(DteFactura $dte): View
    {
        $this->authorize('view', $dte);

        return view('vendedor.dte.show', ['dte' => $this->reporteFiscalService->detail($dte)]);
    }

    /**
     * Solicita anulacion de DTE.
     */
    public function anular(AnularDteRequest $request, DteFactura $dte): RedirectResponse
    {
        $this->authorize('anular', $dte);
        $this->dteAnulacionService->anular($dte, $request->validated(), $request->user());

        return back()->with('success', 'Anulacion de DTE solicitada correctamente.');
    }
}
