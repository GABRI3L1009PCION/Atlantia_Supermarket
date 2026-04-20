<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistroService;
use App\Services\Carrito\CarritoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
    public function __construct(
        private readonly RegistroService $registroService,
        private readonly CarritoService $carritoService
    ) {
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
            $guestSessionId = $request->session()->getId();
            $user = $this->registroService->register($request->validated());
            Auth::login($user);
            $request->session()->regenerate();
            $this->carritoService->mergeGuestCartIntoUser($guestSessionId, $user);

            return redirect()->route('cliente.carrito.index')->with(
                'success',
                "Cuenta creada para {$user->email}. Revisa tu correo para verificarla antes del checkout."
            );
        } catch (Throwable) {
            return back()->withInput()->with('error', 'No fue posible completar el registro.');
        }
    }
}
