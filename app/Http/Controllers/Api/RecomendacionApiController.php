<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RecommendationRequest;
use App\Services\Ml\RecomendacionService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de recomendaciones ML.
 */
class RecomendacionApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RecomendacionService $recomendacionService)
    {
    }

    /**
     * Devuelve recomendaciones para el cliente.
     */
    public function index(RecommendationRequest $request): JsonResponse
    {
        $this->authorize('viewRecommendations', $request->user());

        return response()->json([
            'message' => 'Recomendaciones obtenidas.',
            'data' => $this->recomendacionService->forCustomer($request->user(), $request->validated()),
        ]);
    }
}
