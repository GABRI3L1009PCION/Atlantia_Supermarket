<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Devolucion\ResolverDevolucionRequest;
use App\Models\Devolucion;
use App\Services\Pedidos\DevolucionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador administrativo de devoluciones.
 */
class DevolucionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly DevolucionService $devolucionService)
    {
    }

    /**
     * Lista devoluciones pendientes.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Devolucion::class);

        return view('admin.devoluciones.index', ['devoluciones' => $this->devolucionService->pendientes()]);
    }

    /**
     * Aprueba o rechaza una devolucion.
     */
    public function update(ResolverDevolucionRequest $request, Devolucion $devolucion): RedirectResponse
    {
        $this->authorize('update', $devolucion);
        $this->devolucionService->resolver($devolucion, $request->user(), $request->validated());

        return back()->with('success', 'Devolucion resuelta correctamente.');
    }
}
