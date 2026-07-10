<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\CourierWalletService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly CourierWalletService $walletService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ganancias obtenidas.',
            'data' => $this->payload->walletSummary($this->walletService->summary($request->user())),
        ]);
    }
}
