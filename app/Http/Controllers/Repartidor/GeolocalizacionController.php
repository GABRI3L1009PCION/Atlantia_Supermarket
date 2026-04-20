<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\ActualizarGpsRequest;
use App\Services\Geolocalizacion\SeguimientoGpsService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de ubicacion GPS del repartidor.
 */
class GeolocalizacionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly SeguimientoGpsService $seguimientoGpsService)
    {
    }

    /**
     * Guarda una ubicacion GPS.
     */
    public function store(ActualizarGpsRequest $request): JsonResponse
    {
        $this->authorize('sendLocation', $request->user());
        $status = $this->seguimientoGpsService->storeLocation($request->user(), $request->validated());

        return response()->json(['message' => 'Ubicacion registrada.', 'data' => $status], 201);
    }
}
