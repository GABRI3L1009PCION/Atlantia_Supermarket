<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\StorePerfilFiscalRequest;
use App\Services\Fel\PerfilFiscalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del perfil fiscal FEL del vendedor.
 */
class PerfilFiscalController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PerfilFiscalService $perfilFiscalService)
    {
    }

    /**
     * Muestra perfil fiscal del vendedor.
     */
    public function edit(Request $request): View
    {
        $this->authorize('manageFiscalProfile', $request->user());

        return view('vendedor.perfil-fiscal.edit', ['perfil' => $this->perfilFiscalService->detail($request->user())]);
    }

    /**
     * Actualiza perfil fiscal.
     */
    public function update(StorePerfilFiscalRequest $request): RedirectResponse
    {
        $this->authorize('manageFiscalProfile', $request->user());
        $this->perfilFiscalService->update($request->user(), $request->validated());

        return back()->with('success', 'Perfil fiscal actualizado correctamente.');
    }
}
