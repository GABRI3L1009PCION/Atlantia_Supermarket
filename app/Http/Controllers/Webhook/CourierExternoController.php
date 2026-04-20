<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\CourierExternoWebhookRequest;
use App\Services\Geolocalizacion\CourierWebhookService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de webhooks de courier externo.
 */
class CourierExternoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CourierWebhookService $courierWebhookService)
    {
    }

    /**
     * Recibe evento de courier externo.
     */
    public function __invoke(CourierExternoWebhookRequest $request): JsonResponse
    {
        $this->courierWebhookService->handle($request->validated(), $request->headers->all());

        return response()->json(['message' => 'Webhook de courier recibido.', 'data' => []]);
    }
}
