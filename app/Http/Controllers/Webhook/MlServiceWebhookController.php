<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\MlServiceWebhookRequest;
use App\Services\Ml\MlWebhookService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de webhooks del microservicio ML.
 */
class MlServiceWebhookController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly MlWebhookService $mlWebhookService)
    {
    }

    /**
     * Recibe evento del microservicio ML.
     */
    public function __invoke(MlServiceWebhookRequest $request): JsonResponse
    {
        $this->mlWebhookService->handle($request->validated(), $request->headers->all());

        return response()->json(['message' => 'Webhook ML recibido.', 'data' => []]);
    }
}
