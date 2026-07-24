<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\StoreCourierCashSettlementRequest;
use App\Http\Requests\Repartidor\StoreCourierWithdrawalRequest;
use App\Http\Requests\Repartidor\UpdateCourierBankAccountRequest;
use App\Services\Repartidores\CourierFinanceService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    public function __construct(
        private readonly CourierFinanceService $financeService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function updateBankAccount(UpdateCourierBankAccountRequest $request): JsonResponse
    {
        $profile = $this->financeService->updateBankAccount($request->user(), $request->validated());

        return response()->json([
            'message' => 'Cuenta bancaria actualizada.',
            'data' => $this->payload->bankAccount($profile),
        ]);
    }

    public function storeWithdrawal(StoreCourierWithdrawalRequest $request): JsonResponse
    {
        $withdrawal = $this->financeService->requestWithdrawal($request->user(), $request->validated());

        return response()->json([
            'message' => 'Retiro enviado a revision financiera.',
            'data' => $this->payload->withdrawal($withdrawal),
        ], 201);
    }

    public function storeCashSettlement(StoreCourierCashSettlementRequest $request): JsonResponse
    {
        $settlement = $this->financeService->requestCashSettlement($request->user(), $request->validated());

        return response()->json([
            'message' => 'Liquidacion de efectivo enviada.',
            'data' => $this->payload->cashSettlement($settlement),
        ], 201);
    }
}
