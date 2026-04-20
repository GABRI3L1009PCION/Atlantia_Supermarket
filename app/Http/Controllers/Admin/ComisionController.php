<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Comision\UpdateComisionRequest;
use App\Models\VendorCommission;
use App\Services\Comisiones\CalculadoraComisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de comisiones.
 */
class ComisionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CalculadoraComisionService $calculadoraComisionService)
    {
    }

    /**
     * Lista comisiones mensuales.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VendorCommission::class);

        return view('admin.comisiones.index', [
            'comisiones' => $this->calculadoraComisionService->paginate($request->all()),
        ]);
    }

    /**
     * Actualiza una comision.
     */
    public function update(UpdateComisionRequest $request, VendorCommission $comision): RedirectResponse
    {
        $this->authorize('update', $comision);
        $this->calculadoraComisionService->update($comision, $request->validated(), $request->user());

        return back()->with('success', 'Comision actualizada correctamente.');
    }
}
