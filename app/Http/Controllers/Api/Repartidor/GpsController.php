<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\ActualizarGpsRequest;
use App\Services\Geolocalizacion\SeguimientoGpsService;
use Illuminate\Http\JsonResponse;

class GpsController extends Controller
{
    public function __construct(private readonly SeguimientoGpsService $seguimientoGpsService) {}

    public function store(ActualizarGpsRequest $request): JsonResponse
    {
        $this->authorize('sendLocation', $request->user());
        $status = $this->seguimientoGpsService
            ->storeLocation($request->user(), $request->validated())
            ->load(['pedido', 'externalDeliveryOrder']);

        return response()->json([
            'message' => 'Ubicacion registrada.',
            'data' => [
                'id' => $status->id,
                'pedido_id' => $status->pedido?->uuid,
                'external_order_id' => $status->externalDeliveryOrder?->uuid,
                'latitude' => (float) $status->latitude,
                'longitude' => (float) $status->longitude,
                'estado' => $status->estado,
                'battery_level' => $status->battery_level,
                'accuracy_meters' => $status->accuracy_meters === null ? null : (float) $status->accuracy_meters,
                'timestamp_gps' => $status->timestamp_gps?->toIso8601String(),
            ],
        ], 201);
    }
}
