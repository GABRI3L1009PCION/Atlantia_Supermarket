<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\Transferencia\ValidateTransferRequest;
use App\Models\Payment;
use App\Services\Pagos\ValidadorTransferenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de validacion de transferencias bancarias.
 */
class ValidacionTransferenciaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ValidadorTransferenciaService $validadorTransferenciaService)
    {
    }

    /**
     * Lista transferencias pendientes.
     */
    public function index(Request $request): View
    {
        $this->authorize('validateTransfers', Payment::class);

        return view('empleado.transferencias.index', [
            'payments' => $this->validadorTransferenciaService->pending($request->all()),
        ]);
    }

    /**
     * Valida o rechaza una transferencia.
     */
    public function update(ValidateTransferRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('validateTransfer', $payment);
        $this->validadorTransferenciaService->validar($payment, $request->validated(), $request->user());

        return back()->with('success', 'Transferencia procesada correctamente.');
    }
}
