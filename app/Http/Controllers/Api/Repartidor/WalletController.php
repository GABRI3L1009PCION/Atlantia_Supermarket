<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\CourierFinanceService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly CourierFinanceService $financeService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ganancias obtenidas.',
            'data' => $this->payload->walletSummary($this->financeService->summary($request->user())),
        ]);
    }
}
