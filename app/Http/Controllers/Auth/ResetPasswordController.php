<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador para restablecer passwords.
 */
class ResetPasswordController extends Controller
{
    /**
     * Crea una instancia del controlador.
     *
     * @param PasswordResetService $passwordResetService
     */
    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    /**
     * Muestra el formulario de nuevo password.
     *
     * @param Request $request
     * @param string $token
     * @return View
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    /**
     * Actualiza el password del usuario.
     *
     * @param ResetPasswordRequest $request
     * @return RedirectResponse
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $this->passwordResetService->resetPassword($request->validated());

            return redirect()->route('login')->with('success', 'Password actualizado correctamente.');
        } catch (Throwable) {
            return back()->withInput($request->only('email'))->with('error', 'El enlace no es valido o expiro.');
        }
    }
}
