<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pedido\UpdatePedidoRequest;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Pedidos\PedidoAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de pedidos.
 */
class PedidoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PedidoAdminService $pedidoAdminService)
    {
    }

    /**
     * Lista pedidos del sistema.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pedido::class);

        return view('admin.pedidos.index', ['pedidos' => $this->pedidoAdminService->paginate($request->all())]);
    }

    /**
     * Muestra detalle de pedido.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('view', $pedido);

        return view('admin.pedidos.show', [
            'pedido' => $this->pedidoAdminService->detail($pedido),
            'repartidores' => User::query()->role('repartidor')->orderBy('name')->get(),
        ]);
    }

    /**
     * Actualiza estado y asignacion del pedido.
     */
    public function update(UpdatePedidoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('update', $pedido);
        $this->pedidoAdminService->update($pedido, $request->validated(), $request->user());

        return back()->with('success', 'Pedido actualizado correctamente.');
    }
}
