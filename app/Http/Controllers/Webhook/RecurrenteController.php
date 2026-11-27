<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Pagos\Recurrente\RecurrentePagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recibe los webhooks de Recurrente (firma verificada por middleware).
 */
class RecurrenteController extends Controller
{
    public function __construct(private readonly RecurrentePagoService $recurrentePagoService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $evento = json_decode($request->getContent(), true);

        if (! is_array($evento)) {
            return response()->json(['message' => 'Cuerpo de webhook invalido.'], 422);
        }

        $this->recurrentePagoService->procesarEvento($evento);

        return response()->json(['message' => 'Webhook de Recurrente recibido.']);
    }
}
