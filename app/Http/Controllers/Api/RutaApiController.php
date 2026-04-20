<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoutePreviewRequest;
use App\Models\Pedido;
use App\Services\Geolocalizacion\RutaOptimaService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de rutas.
 */
class RutaApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RutaOptimaService $rutaOptimaService)
    {
    }

    /**
     * Calcula vista previa de ruta.
     */
    public function preview(RoutePreviewRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ruta calculada.',
            'data' => $this->rutaOptimaService->preview($request->validated()),
        ]);
    }

    /**
     * Obtiene ruta real de un pedido.
     */
    public function show(Pedido $pedido): JsonResponse
    {
        $this->authorize('track', $pedido);

        return response()->json(['message' => 'Ruta obtenida.', 'data' => $this->rutaOptimaService->forPedido($pedido)]);
    }
}
