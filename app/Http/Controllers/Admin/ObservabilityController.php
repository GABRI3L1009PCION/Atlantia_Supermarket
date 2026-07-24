<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Observability\PlatformHealthService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel operativo de observabilidad e incidentes.
 */
class ObservabilityController extends Controller
{
    public function __construct(private readonly PlatformHealthService $platformHealthService) {}

    /**
     * Muestra salud tecnica, alertas y contexto de release.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAdminDashboard', $request->user());

        return view('admin.observabilidad.index', [
            'snapshot' => $this->platformHealthService->operationsSnapshot(),
        ]);
    }
}
