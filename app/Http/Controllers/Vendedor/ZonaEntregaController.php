<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\ZonaEntrega\SyncVendorZonaRequest;
use App\Services\Geolocalizacion\VendorZonaEntregaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de zonas de entrega del vendedor.
 */
class ZonaEntregaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly VendorZonaEntregaService $vendorZonaEntregaService)
    {
    }

    /**
     * Muestra zonas configuradas.
     */
    public function index(Request $request): View
    {
        $this->authorize('manageVendorZones', $request->user());

        return view('vendedor.zonas-entrega.index', ['zonas' => $this->vendorZonaEntregaService->forVendor($request->user())]);
    }

    /**
     * Sincroniza zonas de entrega.
     */
    public function sync(SyncVendorZonaRequest $request): RedirectResponse
    {
        $this->authorize('manageVendorZones', $request->user());
        $this->vendorZonaEntregaService->sync($request->user(), $request->validated());

        return back()->with('success', 'Zonas de entrega actualizadas correctamente.');
    }
}
