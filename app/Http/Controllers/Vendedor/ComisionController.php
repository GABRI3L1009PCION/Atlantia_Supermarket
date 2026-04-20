<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\VendorCommission;
use App\Services\Comisiones\CalculadoraComisionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de comisiones del vendedor.
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
     * Lista comisiones propias.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnCommissions', VendorCommission::class);

        return view('vendedor.comisiones.index', [
            'comisiones' => $this->calculadoraComisionService->paginateForVendor($request->user()),
        ]);
    }

    /**
     * Muestra detalle de comision propia.
     */
    public function show(VendorCommission $comision): View
    {
        $this->authorize('view', $comision);

        return view('vendedor.comisiones.show', ['comision' => $this->calculadoraComisionService->detail($comision)]);
    }
}
