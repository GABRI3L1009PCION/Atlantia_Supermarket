<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\CourierSupportTicketRequest;
use App\Http\Requests\Repartidor\EmergencyRequest;
use App\Services\Repartidores\CourierSupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
