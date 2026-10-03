<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Exceptions\TransaccionFallidaException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOffer;
use App\Services\Repartidores\DeliveryOfferService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(
        private readonly DeliveryOfferService $offerService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ofertas obtenidas.',
            'data' => $this->offerService->activeFor($request->user())
                ->map(fn (DeliveryOffer $offer): array => $this->payload->offer($offer))
                ->values(),
        ]);
    }

    public function accept(Request $request, DeliveryOffer $offer): JsonResponse
    {
        try {
            $offer = $this->offerService->accept($offer, $request->user());
        } catch (TransaccionFallidaException $exception) {
            return response()->json(['message' => $exception->publicMessage()], 422);
        }

        return response()->json([
            'message' => 'Oferta aceptada.',
            'data' => $this->payload->offer($offer),
        ]);
    }

    public function reject(Request $request, DeliveryOffer $offer): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $offer = $this->offerService->reject($offer, $request->user(), $data['reason'] ?? null);
        } catch (TransaccionFallidaException $exception) {
            return response()->json(['message' => $exception->publicMessage()], 422);
        }

        return response()->json([
            'message' => 'Oferta rechazada.',
            'data' => $this->payload->offer($offer),
        ]);
    }
}
