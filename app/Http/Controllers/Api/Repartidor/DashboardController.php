<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\DashboardRepartidorService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardRepartidorService $dashboardService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard obtenido.',
            'data' => $this->payload->dashboard($this->dashboardService->metrics($request->user())),
        ]);
    }
}
