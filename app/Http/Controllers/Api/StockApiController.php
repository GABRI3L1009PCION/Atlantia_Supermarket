<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StockAvailabilityRequest;
use App\Models\Producto;
use App\Services\Inventario\StockService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de disponibilidad de stock.
 */
class StockApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Consulta disponibilidad de un producto.
     */
    public function show(StockAvailabilityRequest $request, Producto $producto): JsonResponse
    {
        $this->authorize('viewCatalogo', $producto);

        return response()->json([
            'message' => 'Disponibilidad consultada.',
            'data' => $this->stockService->availability($producto, $request->validated()),
        ]);
    }
}
