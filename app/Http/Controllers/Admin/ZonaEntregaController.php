<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ZonaEntrega\StoreZonaEntregaRequest;
use App\Http\Requests\Admin\ZonaEntrega\UpdateZonaEntregaRequest;
use App\Models\DeliveryZone;
use App\Services\Geolocalizacion\ZonaEntregaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de zonas de entrega.
 */
class ZonaEntregaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ZonaEntregaService $zonaEntregaService)
    {
    }

    /**
     * Lista zonas de entrega.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DeliveryZone::class);

        return view('admin.zonas-entrega.index', [
            'zonas' => $this->zonaEntregaService->paginate($request->all()),
            'zonasActivas' => $this->zonaEntregaService->activeCached(),
        ]);
    }

    /**
     * Crea una zona.
     */
    public function store(StoreZonaEntregaRequest $request): RedirectResponse
    {
        $this->authorize('create', DeliveryZone::class);
        $this->zonaEntregaService->create($request->validated());

        return back()->with('success', 'Zona de entrega creada correctamente.');
    }

    /**
     * Actualiza una zona.
     */
    public function update(UpdateZonaEntregaRequest $request, DeliveryZone $zona): RedirectResponse
    {
        $this->authorize('update', $zona);
        $this->zonaEntregaService->update($zona, $request->validated());

        return back()->with('success', 'Zona de entrega actualizada correctamente.');
    }

    /**
     * Elimina logicamente una zona.
     */
    public function destroy(DeliveryZone $zona): RedirectResponse
    {
        $this->authorize('delete', $zona);
        $this->zonaEntregaService->delete($zona);

        return back()->with('success', 'Zona de entrega eliminada correctamente.');
    }
}
