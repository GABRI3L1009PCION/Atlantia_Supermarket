<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\CourierSupportTicketRequest;
use App\Http\Requests\Repartidor\EmergencyRequest;
use App\Models\CourierSupportTicket;
use App\Services\Repartidores\CourierSupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador de soporte y emergencias.
 */
class SoporteController extends Controller
{
    public function __construct(private readonly CourierSupportService $supportService) {}

    /**
     * Muestra centro de soporte.
     */
    public function index(Request $request): View
    {
        return view('repartidor.soporte', [
            'supportCenter' => $this->supportService->supportCenter(),
            'tickets' => $this->supportService->recentFor($request->user()),
        ]);
    }

    /**
     * Abre ticket.
     */
    public function store(CourierSupportTicketRequest $request): RedirectResponse
    {
        $this->supportService->create($request->user(), $request->validated());

        return back()->with('success', 'Caso enviado a soporte local.');
    }

    /**
     * Reporta emergencia.
     */
    public function emergency(EmergencyRequest $request): RedirectResponse
    {
        $this->supportService->emergency($request->user(), $request->validated());

        return back()->with('success', 'Emergencia reportada. Soporte local priorizara tu caso.');
    }

    public function reply(Request $request, CourierSupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $this->supportService->replyAsCourier($ticket, $request->user(), $data);

        return back()->with('success', 'Mensaje enviado a soporte.');
    }

    public function updateStatus(Request $request, CourierSupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['closed'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->supportService->updateStatus($ticket, $data['status'], $data['note'] ?? null, $request->user());

        return back()->with('success', 'Caso cerrado correctamente.');
    }
}
