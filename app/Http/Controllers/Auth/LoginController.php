<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador de inicio y cierre de sesion.
 */
class LoginController extends Controller
{
    /**
     * Crea una instancia del controlador.
     *
     * @param LoginService $loginService
     */
    public function __construct(private readonly LoginService $loginService)
    {
    }

    /**
     * Muestra el formulario de inicio de sesion.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesion.
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $redirectRoute = $this->loginService->authenticate($request->validated(), $request);

            return redirect()->route($redirectRoute)->with('success', 'Sesion iniciada correctamente.');
        } catch (Throwable) {
            return back()->withInput($request->only('email'))->with('error', 'No fue posible iniciar sesion.');
        }
    }

    /**
     * Cierra la sesion activa.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->loginService->logout($request);

        return redirect()->route('login')->with('success', 'Sesion cerrada correctamente.');
    }
}
