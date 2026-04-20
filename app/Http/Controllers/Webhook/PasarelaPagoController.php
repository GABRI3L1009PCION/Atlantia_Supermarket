<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\PasarelaPagoWebhookRequest;
use App\Services\Pagos\PasarelaPagoService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de webhooks de pasarela de pago.
 */
class PasarelaPagoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PasarelaPagoService $pasarelaPagoService)
    {
    }

    /**
     * Recibe evento de la pasarela.
     */
    public function __invoke(PasarelaPagoWebhookRequest $request): JsonResponse
    {
        $this->pasarelaPagoService->confirmarDesdeWebhook($request->validated(), $request->headers->all());

        return response()->json(['message' => 'Webhook de pago recibido.', 'data' => []]);
    }
}
