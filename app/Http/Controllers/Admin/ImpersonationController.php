<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de impersonacion operativa para super admin.
 */
class ImpersonationController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ImpersonationService $impersonationService)
    {
    }

    /**
     * Inicia la impersonacion de un usuario.
     */
    public function start(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('impersonate', $usuario);
        $route = $this->impersonationService->start($request, $request->user(), $usuario);

        return redirect()->route($route)->with('success', 'Modo impersonacion activado correctamente.');
    }

    /**
     * Finaliza la impersonacion activa.
     */
    public function stop(Request $request): RedirectResponse
    {
        abort_unless($this->impersonationService->isActive($request), 403);

        $route = $this->impersonationService->stop($request);

        abort_if($route === null, 403);

        return redirect()->route($route)->with('success', 'Sesion restaurada al super administrador.');
    }
}
