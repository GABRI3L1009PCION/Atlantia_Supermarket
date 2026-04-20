<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador para solicitar enlaces de recuperacion de password.
 */
class ForgotPasswordController extends Controller
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
     * Muestra el formulario de recuperacion.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envia el enlace de recuperacion.
     *
     * @param ForgotPasswordRequest $request
     * @return RedirectResponse
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        try {
            $this->passwordResetService->sendResetLink($request->validated());

            return back()->with('success', 'Si el correo existe, enviaremos instrucciones de recuperacion.');
        } catch (Throwable) {
            return back()->withInput()->with('error', 'No fue posible procesar la solicitud.');
        }
    }
}
