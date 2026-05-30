<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nomina\StoreNominaRequest;
use App\Http\Requests\Admin\Nomina\UpdateNominaDetalleRequest;
use App\Models\Nomina;
use App\Models\NominaDetalle;
use App\Services\Empleados\NominaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Control administrativo de planillas internas.
 */
class NominaController extends Controller
{
    public function __construct(private readonly NominaService $nominaService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Nomina::class);

        return view('admin.nominas.index', [
            'nominas' => $this->nominaService->paginate($request->all()),
            'dashboard' => $this->nominaService->dashboard(),
        ]);
    }

    public function store(StoreNominaRequest $request): RedirectResponse
    {
        $this->authorize('create', Nomina::class);
        $nomina = $this->nominaService->generate($request->validated(), $request->user());

        return redirect()->route('admin.nominas.show', $nomina->uuid)
            ->with('success', 'Nomina generada correctamente.');
    }

    public function show(Nomina $nomina): View
    {
        $this->authorize('view', $nomina);

        return view('admin.nominas.show', [
            'nomina' => $this->nominaService->detail($nomina),
        ]);
    }

    public function updateDetail(
        UpdateNominaDetalleRequest $request,
        Nomina $nomina,
        NominaDetalle $detalle
    ): RedirectResponse {
        $this->authorize('update', $nomina);
        $this->nominaService->updateDetail($nomina, $detalle, $request->validated());

        return back()->with('success', 'Ajuste de nomina actualizado.');
    }

    public function pay(Request $request, Nomina $nomina): RedirectResponse
    {
        $this->authorize('update', $nomina);
        $this->nominaService->markAsPaid($nomina, $request->user());

        return back()->with('success', 'Nomina marcada como pagada.');
    }
}
