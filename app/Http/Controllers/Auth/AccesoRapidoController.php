<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AccesoRapidoService;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

/**
 * Entrar con el numero de telefono (codigo por WhatsApp) o con Google.
 */
class AccesoRapidoController extends Controller
{
    public function __construct(
        private readonly AccesoRapidoService $accesoRapido,
        private readonly LoginService $loginService,
    ) {}

    public function telefono(Request $request): View
    {
        return view('auth.telefono', [
            'numero' => $request->session()->get('acceso_telefono'),
        ]);
    }

    public function enviarCodigo(Request $request): RedirectResponse
    {
        $data = $request->validate(['telefono' => ['required', 'string', 'max:20']]);

        try {
            $numero = $this->accesoRapido->enviarCodigo($data['telefono'], (string) $request->ip());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['telefono' => $exception->getMessage()]);
        }

        $request->session()->put('acceso_telefono', $numero);
        $request->session()->put('acceso_guest_session', $request->session()->getId());

        return redirect()->route('acceso.telefono')->with('success', 'Te enviamos un código por WhatsApp.');
    }

    public function verificarCodigo(Request $request): RedirectResponse
    {
        $numero = $request->session()->get('acceso_telefono');
        if ($numero === null) {
            return redirect()->route('acceso.telefono');
        }

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:10'],
            'nombre' => ['nullable', 'string', 'max:160'],
        ]);

        try {
            $usuario = $this->accesoRapido->verificarCodigo($numero, $data['codigo'], $data['nombre'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['codigo' => $exception->getMessage()]);
        }

        $guestSession = $request->session()->pull('acceso_guest_session');
        $request->session()->forget('acceso_telefono');
        $ruta = $this->loginService->completeAuthenticatedSession($usuario, $request, true, $guestSession);

        return redirect()->intended(route($ruta))->with('success', '¡Listo! Ya iniciaste sesión.');
    }

    public function cambiarNumero(Request $request): RedirectResponse
    {
        $request->session()->forget('acceso_telefono');

        return redirect()->route('acceso.telefono');
    }

    public function google(Request $request): RedirectResponse
    {
        abort_unless(self::googleConfigurado(), 404);
        $request->session()->put('acceso_guest_session', $request->session()->getId());

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        abort_unless(self::googleConfigurado(), 404);

        try {
            $google = Socialite::driver('google')->user();
            $usuario = $this->accesoRapido->usuarioDeGoogle((string) $google->getId(), $google->getEmail(), $google->getName());
        } catch (RuntimeException $exception) {
            return redirect()->route('login')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'No pudimos entrar con Google. Inténtalo de nuevo.');
        }

        $ruta = $this->loginService->completeAuthenticatedSession($usuario, $request, true, $request->session()->pull('acceso_guest_session'));

        return redirect()->intended(route($ruta))->with('success', '¡Listo! Ya iniciaste sesión.');
    }

    public static function googleConfigurado(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
