<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AprobarVendedorRequest;
use App\Http\Requests\Admin\SuspenderVendedorRequest;
use App\Models\Vendor;
use App\Services\Vendedores\VendorAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de vendedores.
 */
class VendedorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly VendorAdminService $vendorAdminService)
    {
    }

    /**
     * Lista solicitudes y vendedores registrados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        return view('admin.vendedores.index', ['vendors' => $this->vendorAdminService->paginate($request->all())]);
    }

    /**
     * Muestra el detalle de un vendedor.
     */
    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        return view('admin.vendedores.show', ['vendor' => $this->vendorAdminService->detail($vendor)]);
    }

    /**
     * Aprueba un vendedor.
     */
    public function approve(AprobarVendedorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        $this->vendorAdminService->approve($vendor, $request->user());

        return back()->with('success', 'Vendedor aprobado correctamente.');
    }

    /**
     * Suspende un vendedor.
     */
    public function suspend(SuspenderVendedorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $this->vendorAdminService->suspend($vendor, $request->validated(), $request->user());

        return back()->with('success', 'Vendedor suspendido correctamente.');
    }
}
