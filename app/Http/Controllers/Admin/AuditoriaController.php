<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Auditoria\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de auditoria.
 */
class AuditoriaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    /**
     * Lista eventos de auditoria.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        return view('admin.auditoria.index', ['logs' => $this->auditoriaService->paginate($request->all())]);
    }

    /**
     * Muestra detalle de un evento.
     */
    public function show(AuditLog $auditLog): View
    {
        $this->authorize('view', $auditLog);

        return view('admin.auditoria.show', ['log' => $this->auditoriaService->detail($auditLog)]);
    }
}
