<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador de autenticacion de dos factores.
 */
class TwoFactorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    /**
     * Muestra el desafio 2FA.
     */
    public function challenge(Request $request): View
    {
        return view('auth.two-factor-challenge', [
            'challenge' => $this->twoFactorService->challengeData($request),
        ]);
    }

    /**
     * Verifica el desafio 2FA.
     */
    public function verify(TwoFactorChallengeRequest $request): RedirectResponse
    {
        try {
            $redirectRoute = $this->twoFactorService->verifyChallenge($request->validated(), $request);

            /* Primera configuración: antes de entrar, mostrar los códigos de respaldo una sola vez. */
            if ($request->session()->has('auth.2fa_codigos_nuevos')) {
                $request->session()->put('auth.2fa_destino', $redirectRoute);

                return redirect()->route('two-factor.respaldo');
            }

            return redirect()->route($redirectRoute)->with('success', 'Verificacion completada.');
        } catch (Throwable) {
            return back()->with('error', 'El codigo de verificacion no es valido.');
        }
    }

    /**
     * Muestra una sola vez los códigos de respaldo recién creados.
     */
    public function respaldo(Request $request): View|RedirectResponse
    {
        $codigos = (array) $request->session()->pull('auth.2fa_codigos_nuevos', []);
        $destino = (string) $request->session()->pull('auth.2fa_destino', 'home');
        $destino = Route::has($destino) ? $destino : 'home';

        if ($codigos === []) {
            return redirect()->route($destino);
        }

        return view('auth.two-factor-respaldo', [
            'codigos' => array_map(fn (string $c): string => substr($c, 0, 5).'-'.substr($c, 5), $codigos),
            'destino' => route($destino),
        ]);
    }

    /**
     * Activa 2FA para el usuario autenticado.
     */
    public function enable(Request $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->twoFactorService->enable($request->user());

        return back()->with('success', 'Segundo factor preparado. Abre tu app autenticadora y confirma el codigo para terminar la activacion.');
    }

    /**
     * Desactiva 2FA para el usuario autenticado.
     */
    public function disable(Request $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->twoFactorService->disable($request->user());

        return back()->with('success', 'Autenticacion de dos factores desactivada.');
    }
}
