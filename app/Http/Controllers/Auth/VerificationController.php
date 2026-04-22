<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Controlador de verificacion de correo electronico.
 */
class VerificationController extends Controller
{
    /**
     * Crea una instancia del controlador.
     *
     * @param EmailVerificationService $emailVerificationService
     */
    public function __construct(private readonly EmailVerificationService $emailVerificationService)
    {
    }

    /**
     * Muestra el aviso de verificacion pendiente.
     *
     * @return View
     */
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Marca el correo como verificado.
     *
     * @param EmailVerificationRequest $request
     * @return RedirectResponse
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $this->emailVerificationService->verify($request->user());

        return redirect()->route('cliente.catalogo.index')->with('success', 'Correo verificado correctamente.');
    }

    /**
     * Verifica el correo con codigo enviado al email.
     *
     * @param Request $request
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el codigo enviado a tu correo.',
            'code.digits' => 'El codigo debe tener 6 digitos.',
        ]);

        $this->emailVerificationService->verifyCode($request->user(), (string) $validated['code']);

        return redirect()->route('cliente.catalogo.index')->with('success', 'Correo verificado correctamente.');
    }

    /**
     * Reenvia la notificacion de verificacion.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function resend(Request $request): RedirectResponse
    {
        try {
            $this->emailVerificationService->resend($request->user());

            return back()->with('success', 'Correo de verificacion reenviado.');
        } catch (Throwable) {
            return back()->with('error', 'No fue posible reenviar el correo de verificacion.');
        }
    }
}
