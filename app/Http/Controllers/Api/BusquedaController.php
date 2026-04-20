<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BusquedaRequest;
use App\Services\Busqueda\MeilisearchService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de busqueda.
 */
class BusquedaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly MeilisearchService $meilisearchService)
    {
    }

    /**
     * Ejecuta busqueda en catalogo.
     */
    public function __invoke(BusquedaRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Busqueda completada.',
            'data' => $this->meilisearchService->search($request->validated()),
        ]);
    }
}
