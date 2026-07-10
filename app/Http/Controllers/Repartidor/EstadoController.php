<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\UpdateAutoAcceptanceRequest;
use App\Http\Requests\Repartidor\UpdateAvailabilityRequest;
use App\Services\Repartidores\CourierProfileService;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de disponibilidad y preferencias del repartidor.
 */
class EstadoController extends Controller
{
    public function __construct(private readonly CourierProfileService $profileService) {}

    /**
     * Actualiza disponibilidad.
     */
    public function availability(UpdateAvailabilityRequest $request): RedirectResponse
    {
        $this->profileService->updateAvailability($request->user(), $request->validated());

        return back()->with('success', 'Estado de conexion actualizado.');
    }

    /**
     * Actualiza aceptacion automatica.
     */
    public function autoAcceptance(UpdateAutoAcceptanceRequest $request): RedirectResponse
    {
        $this->profileService->updateAutoAcceptance($request->user(), $request->validated());

        return back()->with('success', 'Aceptacion automatica actualizada.');
    }
}
