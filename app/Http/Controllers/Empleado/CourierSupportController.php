<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Models\CourierSupportTicket;
use App\Models\User;
use App\Services\Repartidores\CourierSupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierSupportController extends Controller
{
    public function __construct(private readonly CourierSupportService $supportService) {}

    public function index(): View
    {
        return view('empleado.soporte-repartidores.index', [
            'metrics' => $this->supportService->dashboardForOperations(),
        ]);
    }

    public function assign(Request $request, CourierSupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()->findOrFail($data['assigned_to_user_id']);
        $this->supportService->assign($ticket, $assignee, $request->user());

        return back()->with('success', 'Caso asignado correctamente.');
    }

    public function reply(Request $request, CourierSupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $this->supportService->replyFromTeam($ticket, $request->user(), $data);

        return back()->with('success', 'Respuesta enviada al repartidor.');
    }

    public function updateStatus(Request $request, CourierSupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->supportService->updateStatus($ticket, $data['status'], $data['note'] ?? null, $request->user());

        return back()->with('success', 'Estado del caso actualizado.');
    }
}
