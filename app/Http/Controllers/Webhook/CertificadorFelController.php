<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\CertificadorFelWebhookRequest;
use App\Services\Fel\InfileCertificadorService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de webhooks del certificador FEL.
 */
class CertificadorFelController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly InfileCertificadorService $infileCertificadorService)
    {
    }

    /**
     * Recibe evento del certificador FEL.
     */
    public function __invoke(CertificadorFelWebhookRequest $request): JsonResponse
    {
        $this->infileCertificadorService->procesarWebhook($request->validated(), $request->headers->all());

        return response()->json(['message' => 'Webhook FEL recibido.', 'data' => []]);
    }
}
