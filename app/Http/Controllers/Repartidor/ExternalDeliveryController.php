<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\ExternalDeliveryActionRequest;
use App\Models\ExternalDeliveryOrder;
use App\Services\Repartidores\ExternalDeliveryOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de entregas externas asignadas al repartidor.
 */
class ExternalDeliveryController extends Controller
{
    public function __construct(private readonly ExternalDeliveryOrderService $externalDeliveryOrderService) {}

    /**
     * Lista entregas externas.
     */
    public function index(Request $request): View
    {
        return view('repartidor.externas.index', [
            'orders' => $this->externalDeliveryOrderService->assignedTo($request->user()),
        ]);
    }

    /**
     * Muestra entrega externa.
     */
    public function show(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): View
    {
        abort_unless((int) $externalDeliveryOrder->repartidor_id === (int) $request->user()->id, 403);

        return view('repartidor.externas.show', [
            'order' => $this->externalDeliveryOrderService->detail($externalDeliveryOrder),
        ]);
    }

    public function arrivedPickup(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): RedirectResponse
    {
        $this->externalDeliveryOrderService->arrivedPickup($externalDeliveryOrder, $request->user());

        return back()->with('success', 'Llegada a tienda registrada.');
    }

    public function pickupNotReady(ExternalDeliveryActionRequest $request, ExternalDeliveryOrder $externalDeliveryOrder): RedirectResponse
    {
        $this->externalDeliveryOrderService->pickupNotReady(
            $externalDeliveryOrder,
            $request->user(),
            $request->validated('issue_reason')
        );

        return back()->with('success', 'Reporte enviado.');
    }

    public function pickedUp(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): RedirectResponse
    {
        $this->externalDeliveryOrderService->pickedUp($externalDeliveryOrder, $request->user());

        return back()->with('success', 'Pedido externo recogido.');
    }

    public function arrivedCustomer(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): RedirectResponse
    {
        $this->externalDeliveryOrderService->arrivedCustomer($externalDeliveryOrder, $request->user());

        return back()->with('success', 'Llegada al cliente registrada.');
    }

    public function cashIssue(ExternalDeliveryActionRequest $request, ExternalDeliveryOrder $externalDeliveryOrder): RedirectResponse
    {
        $this->externalDeliveryOrderService->reportCashIssue(
            $externalDeliveryOrder,
            $request->user(),
            $request->validated('cash_notes')
        );

        return back()->with('success', 'Reporte de efectivo enviado.');
    }

    public function deliver(ExternalDeliveryActionRequest $request, ExternalDeliveryOrder $externalDeliveryOrder): RedirectResponse
    {
        $this->externalDeliveryOrderService->deliver($externalDeliveryOrder, $request->user(), $request->validated());

        return redirect()->route('repartidor.dashboard')->with('success', 'Entrega externa completada.');
    }
}
