<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador de registro de clientes y solicitudes de vendedor.
 */
class RegisterController extends Controller
{
    /**
     * Crea una instancia del controlador.
     *
     * @param RegistroService $registroService
     */
    public function __construct(private readonly RegistroService $registroService)
    {
    }

    /**
     * Muestra el formulario de registro.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Procesa un nuevo registro.
     *
     * @param RegisterRequest $request
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        try {
            $user = $this->registroService->register($request->validated());

            return redirect()->route('verification.notice')->with(
                'success',
                "Cuenta creada para {$user->email}. Verifica tu correo para continuar."
            );
        } catch (Throwable) {
            return back()->withInput()->with('error', 'No fue posible completar el registro.');
        }
    }
}
