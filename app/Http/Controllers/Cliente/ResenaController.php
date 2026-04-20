<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreResenaRequest;
use App\Models\Pedido;
use App\Models\Resena;
use App\Services\Resenas\ResenaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de resenas creadas por clientes.
 */
class ResenaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ResenaService $resenaService)
    {
    }

    /**
     * Muestra resenas del cliente.
     */
    public function index(Request $request): View
    {
        return view('cliente.resenas.index', ['resenas' => $this->resenaService->forUser($request->user())]);
    }

    /**
     * Guarda una resena verificada.
     */
    public function store(StoreResenaRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('review', $pedido);
        $this->resenaService->create($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Resena enviada para moderacion.');
    }

    /**
     * Elimina una resena propia.
     */
    public function destroy(Resena $resena): RedirectResponse
    {
        $this->authorize('delete', $resena);
        $this->resenaService->delete($resena);

        return back()->with('success', 'Resena eliminada correctamente.');
    }
}
