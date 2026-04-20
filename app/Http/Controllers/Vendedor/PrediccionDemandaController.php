<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Ml\PrediccionDemandaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de predicciones de demanda del vendedor.
 */
class PrediccionDemandaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PrediccionDemandaService $prediccionDemandaService)
    {
    }

    /**
     * Lista predicciones por producto propio.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnPredictions', $request->user());

        return view('vendedor.predicciones.index', [
            'predicciones' => $this->prediccionDemandaService->forVendor($request->user()),
        ]);
    }
}
