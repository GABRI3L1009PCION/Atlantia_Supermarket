<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\CourierProfileService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoController extends Controller
{
    public function __construct(
        private readonly CourierProfileService $profileService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'availability_status' => ['required', Rule::in(['offline', 'available', 'busy', 'paused', 'emergency'])],
            'service_scope' => ['nullable', Rule::in(['internal', 'entrepreneurs', 'external', 'both'])],
            'vehicle_type' => ['nullable', 'string', 'max:60'],
        ]);

        $profile = $this->profileService->updateAvailability($request->user(), $data);

        return response()->json([
            'message' => 'Estado actualizado.',
            'data' => $this->payload->profile($profile),
        ]);
    }

    public function autoAcceptance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'auto_accept_enabled' => ['required', 'boolean'],
            'auto_accept_max_distance_km' => ['nullable', 'numeric', 'min:0.5', 'max:50'],
        ]);

        $profile = $this->profileService->updateAutoAcceptance($request->user(), $data);

        return response()->json([
            'message' => 'Aceptacion automatica actualizada.',
            'data' => $this->payload->profile($profile),
        ]);
    }
}
