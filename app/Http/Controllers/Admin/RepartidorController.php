<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Repartidor\StoreRepartidorRequest;
use App\Http\Requests\Admin\Repartidor\UpdateRepartidorRequest;
use App\Models\User;
use App\Services\Logistica\RepartidorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de repartidores.
 */
class RepartidorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RepartidorService $repartidorService)
    {
    }

    /**
     * Lista repartidores.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.repartidores.index', ['repartidores' => $this->repartidorService->paginate($request->all())]);
    }

    /**
     * Registra un repartidor.
     */
    public function store(StoreRepartidorRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $this->repartidorService->create($request->validated());

        return back()->with('success', 'Repartidor creado correctamente.');
    }

    /**
     * Muestra actividad de un repartidor.
     */
    public function show(User $repartidor): View
    {
        $this->authorize('view', $repartidor);

        return view('admin.repartidores.show', ['repartidor' => $this->repartidorService->detail($repartidor)]);
    }

    /**
     * Actualiza un repartidor.
     */
    public function update(UpdateRepartidorRequest $request, User $repartidor): RedirectResponse
    {
        $this->authorize('update', $repartidor);
        $this->repartidorService->update($repartidor, $request->validated());

        return back()->with('success', 'Repartidor actualizado correctamente.');
    }

    /**
     * Elimina logicamente un repartidor.
     */
    public function destroy(User $repartidor): RedirectResponse
    {
        $this->authorize('delete', $repartidor);
        $this->repartidorService->delete($repartidor);

        return redirect()->route('admin.repartidores.index')->with('success', 'Repartidor eliminado correctamente.');
    }
}
