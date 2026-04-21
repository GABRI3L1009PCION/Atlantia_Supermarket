<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Antifraude\ResolveFraudAlertRequest;
use App\Models\Ml\FraudAlert;
use App\Services\Antifraude\DeteccionPatronesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de alertas antifraude.
 */
class AntifraudeController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DeteccionPatronesService $deteccionPatronesService)
    {
    }

    /**
     * Lista alertas antifraude.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', FraudAlert::class);

        return view('admin.antifraude.index', [
            'alerts' => $this->deteccionPatronesService->paginate($request->all()),
            'dashboard' => $this->deteccionPatronesService->dashboard($request->all()),
        ]);
    }

    /**
     * Muestra detalle de una alerta antifraude.
     */
    public function show(FraudAlert $fraudAlert): View
    {
        $this->authorize('viewAny', FraudAlert::class);

        return view('admin.antifraude.show', ['alert' => $this->deteccionPatronesService->detail($fraudAlert)]);
    }

    /**
     * Resuelve una alerta antifraude.
     */
    public function resolve(ResolveFraudAlertRequest $request, FraudAlert $fraudAlert): RedirectResponse
    {
        $this->authorize('resolve', $fraudAlert);
        $this->deteccionPatronesService->resolve($fraudAlert, $request->validated(), $request->user());

        return back()->with('success', 'Alerta antifraude resuelta correctamente.');
    }
}
