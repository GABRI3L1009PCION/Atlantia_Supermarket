<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PredictionRequest;
use App\Models\Producto;
use App\Services\Ml\PrediccionDemandaService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de predicciones de demanda.
 */
class PrediccionApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PrediccionDemandaService $prediccionDemandaService)
    {
    }

    /**
     * Devuelve prediccion para un producto.
     */
    public function show(PredictionRequest $request, Producto $producto): JsonResponse
    {
        $this->authorize('viewDemandPrediction', $producto);

        return response()->json([
            'message' => 'Prediccion obtenida.',
            'data' => $this->prediccionDemandaService->forProduct($producto, $request->validated()),
        ]);
    }
}
