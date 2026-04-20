<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarritoApiRequest;
use App\Services\Carrito\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API de carrito.
 */
class CarritoApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CarritoService $carritoService)
    {
    }

    /**
     * Devuelve el carrito actual.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Carrito obtenido.', 'data' => $this->carritoService->current($request)]);
    }

    /**
     * Sincroniza items del carrito.
     */
    public function sync(CarritoApiRequest $request): JsonResponse
    {
        $carrito = $this->carritoService->sync($request, $request->validated());

        return response()->json(['message' => 'Carrito sincronizado.', 'data' => $carrito]);
    }
}
