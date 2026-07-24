<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\StoreCourierCashSettlementRequest;
use App\Http\Requests\Repartidor\StoreCourierWithdrawalRequest;
use App\Http\Requests\Repartidor\UpdateCourierBankAccountRequest;
use App\Services\Repartidores\CourierFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de ganancias y billetera.
 */
class GananciasController extends Controller
{
    public function __construct(private readonly CourierFinanceService $financeService) {}

    /**
     * Muestra billetera.
     */
    public function __invoke(Request $request): View
    {
        return view('repartidor.ganancias', [
            'summary' => $this->financeService->summary($request->user()),
        ]);
    }

    public function updateBankAccount(UpdateCourierBankAccountRequest $request): RedirectResponse
    {
        $this->financeService->updateBankAccount($request->user(), $request->validated());

        return back()->with('success', 'Cuenta bancaria actualizada. Se enviara a verificacion.');
    }

    public function requestWithdrawal(StoreCourierWithdrawalRequest $request): RedirectResponse
    {
        $this->financeService->requestWithdrawal($request->user(), $request->validated());

        return back()->with('success', 'Retiro enviado a revision financiera.');
    }

    public function requestCashSettlement(StoreCourierCashSettlementRequest $request): RedirectResponse
    {
        $this->financeService->requestCashSettlement($request->user(), $request->validated());

        return back()->with('success', 'Liquidacion de efectivo enviada correctamente.');
    }
}
