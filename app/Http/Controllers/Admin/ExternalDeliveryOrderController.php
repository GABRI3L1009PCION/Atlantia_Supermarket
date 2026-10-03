<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExternalDelivery\AssignExternalDeliveryOrderRequest;
use App\Http\Requests\Admin\ExternalDelivery\StoreExternalDeliveryOrderRequest;
use App\Models\ExternalDeliveryOrder;
use App\Models\User;
use App\Services\Repartidores\ExternalDeliveryOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de entregas externas.
 */
class ExternalDeliveryOrderController extends Controller
{
    public function __construct(private readonly ExternalDeliveryOrderService $externalDeliveryOrderService) {}

    /**
     * Lista solicitudes externas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAdminDashboard');

        return view('admin.entregas-externas.index', [
            'orders' => $this->externalDeliveryOrderService->paginate($request->all()),
            'repartidores' => User::query()->role('repartidor')->active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Guarda solicitud externa.
     */
    public function store(StoreExternalDeliveryOrderRequest $request): RedirectResponse
    {
        $order = $this->externalDeliveryOrderService->create($request->validated());

        return redirect()->route('admin.entregas-externas.show', $order)
            ->with('success', 'Solicitud externa creada.');
    }

    /**
     * Muestra solicitud externa.
     */
    public function show(ExternalDeliveryOrder $externalDeliveryOrder): View
    {
        $this->authorize('viewAdminDashboard');

        return view('admin.entregas-externas.show', [
            'order' => $this->externalDeliveryOrderService->detail($externalDeliveryOrder),
            'repartidores' => User::query()->role('repartidor')->active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Asigna solicitud externa a repartidor.
     */
    public function assign(AssignExternalDeliveryOrderRequest $request, ExternalDeliveryOrder $externalDeliveryOrder): RedirectResponse
    {
        $repartidor = User::query()->role('repartidor')->findOrFail($request->validated('repartidor_id'));
        $this->externalDeliveryOrderService->assign($externalDeliveryOrder, $repartidor, $request->validated());

        return back()->with('success', 'Oferta enviada al repartidor.');
    }
}
