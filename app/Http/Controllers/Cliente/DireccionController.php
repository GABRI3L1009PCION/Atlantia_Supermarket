<?php

namespace App\Http\Controllers\Cliente;

use App\DTOs\DireccionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreDireccionRequest;
use App\Http\Requests\Cliente\Direccion\UpdateDireccionRequest;
use App\Models\Cliente\Direccion;
use App\Services\Clientes\DireccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de direcciones de entrega del cliente.
 */
class DireccionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DireccionService $direccionService)
    {
    }

    /**
     * Lista direcciones del cliente.
     */
    public function index(Request $request): View
    {
        return view('cliente.direcciones.index', ['direcciones' => $this->direccionService->forUser($request->user())]);
    }

    /**
     * Guarda una direccion.
     */
    public function store(StoreDireccionRequest $request): RedirectResponse
    {
        $this->authorize('create', Direccion::class);
        $this->direccionService->create($request->user(), DireccionDTO::fromArray($request->validated()));

        return back()->with('success', 'Direccion guardada correctamente.');
    }

    /**
     * Actualiza una direccion.
     */
    public function update(UpdateDireccionRequest $request, Direccion $direccion): RedirectResponse
    {
        $this->authorize('update', $direccion);
        $this->direccionService->update($direccion, DireccionDTO::fromArray($request->validated()));

        return back()->with('success', 'Direccion actualizada correctamente.');
    }

    /**
     * Elimina una direccion.
     */
    public function destroy(Direccion $direccion): RedirectResponse
    {
        $this->authorize('delete', $direccion);
        $this->direccionService->delete($direccion);

        return back()->with('success', 'Direccion eliminada correctamente.');
    }
}
