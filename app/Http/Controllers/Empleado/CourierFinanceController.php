<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\UpdateCourierBankVerificationRequest;
use App\Http\Requests\Empleado\UpdateCourierCashSettlementRequest;
use App\Http\Requests\Empleado\UpdateCourierWithdrawalRequest;
use App\Models\CourierCashSettlement;
use App\Models\CourierProfile;
use App\Models\CourierWithdrawalRequest;
use App\Services\Repartidores\CourierFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierFinanceController extends Controller
{
    public function __construct(private readonly CourierFinanceService $financeService) {}

    public function index(Request $request): View
    {
        return view('empleado.finanzas-repartidores.index', [
            'metrics' => $this->financeService->dashboardForOperations(),
        ]);
    }

    public function verifyBankAccount(UpdateCourierBankVerificationRequest $request, CourierProfile $courierProfile): RedirectResponse
    {
        $this->financeService->verifyBankAccount($courierProfile, $request->validated(), $request->user());

        return back()->with('success', 'Verificacion bancaria actualizada.');
    }

    public function updateWithdrawal(UpdateCourierWithdrawalRequest $request, CourierWithdrawalRequest $withdrawal): RedirectResponse
    {
        $this->financeService->reviewWithdrawal($withdrawal, $request->validated(), $request->user());

        return back()->with('success', 'Solicitud de retiro procesada.');
    }

    public function updateCashSettlement(UpdateCourierCashSettlementRequest $request, CourierCashSettlement $settlement): RedirectResponse
    {
        $this->financeService->reviewCashSettlement($settlement, $request->validated(), $request->user());

        return back()->with('success', 'Liquidacion procesada correctamente.');
    }
}
