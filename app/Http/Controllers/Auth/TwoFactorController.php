<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador de autenticacion de dos factores.
 */
class TwoFactorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     *
     * @param TwoFactorService $twoFactorService
     */
    public function __construct(private readonly TwoFactorService $twoFactorService)
    {
    }

    /**
     * Muestra el desafio 2FA.
     *
     * @return View
     */
    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    /**
     * Verifica el desafio 2FA.
     *
     * @param TwoFactorChallengeRequest $request
     * @return RedirectResponse
     */
    public function verify(TwoFactorChallengeRequest $request): RedirectResponse
    {
        try {
            $redirectRoute = $this->twoFactorService->verifyChallenge($request->validated(), $request);

            return redirect()->route($redirectRoute)->with('success', 'Verificacion completada.');
        } catch (Throwable) {
            return back()->with('error', 'El codigo de verificacion no es valido.');
        }
    }

    /**
     * Activa 2FA para el usuario autenticado.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function enable(Request $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->twoFactorService->enable($request->user());

        return back()->with('success', 'Autenticacion de dos factores activada.');
    }

    /**
     * Desactiva 2FA para el usuario autenticado.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function disable(Request $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->twoFactorService->disable($request->user());

        return back()->with('success', 'Autenticacion de dos factores desactivada.');
    }
}
