<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\Perfil\UpdatePerfilRequest;
use App\Services\Clientes\PerfilClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de perfil del cliente.
 */
class PerfilController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PerfilClienteService $perfilClienteService)
    {
    }

    /**
     * Muestra el perfil del cliente.
     */
    public function edit(Request $request): View
    {
        $this->authorize('update', $request->user());

        return view('cliente.perfil.edit', ['perfil' => $this->perfilClienteService->detail($request->user())]);
    }

    /**
     * Actualiza el perfil del cliente.
     */
    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->perfilClienteService->update($request->user(), $request->validated());

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
