<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Controlador de inicio y cierre de sesion.
 */
class LoginController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly LoginService $loginService) {}

    /**
     * Muestra el formulario de inicio de sesion.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesion.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $redirectRoute = $this->loginService->authenticate($request->validated(), $request);

            return redirect()->route($redirectRoute)->with('success', 'Sesion iniciada correctamente.');
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && $exception->getMessage() === 'Demasiados intentos de inicio de sesion.') {
                abort(429, $exception->getMessage());
            }

            return back()->withInput($request->only('email'))->with('error', 'No fue posible iniciar sesion.');
        }
    }

    /**
     * Cierra la sesion activa.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->loginService->logout($request);

        return redirect()->route('login')->with('success', 'Sesion cerrada correctamente.');
    }
}
