<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ml\MonitorDriftService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de monitoreo ML.
 */
class MlMonitorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly MonitorDriftService $monitorDriftService)
    {
    }

    /**
     * Muestra estado de modelos y metricas.
     */
    public function index(Request $request): View
    {
        $this->authorize('monitorMl', $request->user());

        return view('admin.ml.monitor', [
            'monitor' => $this->monitorDriftService->dashboard($request->all()),
            'filters' => $request->only(['drift_threshold']),
        ]);
    }
}
