<?php

namespace App\Http\Controllers;

use App\Services\Observability\PlatformHealthService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de salud operativa para monitoreo externo.
 */
class HealthController extends Controller
{
    public function __construct(private readonly PlatformHealthService $platformHealthService) {}

    /**
     * Devuelve el estado de dependencias criticas.
     */
    public function __invoke(): JsonResponse
    {
        $payload = $this->platformHealthService->publicHealth();

        return response()->json($payload, $payload['status'] === 'ok' ? 200 : 503);
    }
}
