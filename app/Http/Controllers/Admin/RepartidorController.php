<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Logistica\RepartidorService;
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
        $this->authorize('viewRepartidores', User::class);

        return view('admin.repartidores.index', ['repartidores' => $this->repartidorService->paginate($request->all())]);
    }

    /**
     * Muestra actividad de un repartidor.
     */
    public function show(User $repartidor): View
    {
        $this->authorize('viewRepartidor', $repartidor);

        return view('admin.repartidores.show', ['repartidor' => $this->repartidorService->detail($repartidor)]);
    }
}
