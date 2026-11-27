<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Rules\ContrasenaSegura;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Seguridad de la cuenta: contraseña, verificación en dos pasos y sesiones abiertas.
 */
class SeguridadController extends Controller
{
    public function __construct(private readonly TwoFactorService $dosPasos) {}

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('cuenta.seguridad', [
            'user' => $user,
            'dosPasosActiva' => (bool) $user->two_factor_enabled && $user->two_factor_confirmed_at !== null,
            'codigosRestantes' => $user->two_factor_enabled ? $this->dosPasos->codigosRestantes($user) : 0,
            'codigosNuevos' => session('codigos_respaldo_nuevos'),
            'accesos' => DB::table('login_attempts')
                ->where('user_id', $user->id)
                ->where('successful', true)
                ->latest('attempted_at')
                ->limit(6)
                ->get(['ip_address', 'user_agent', 'attempted_at']),
        ]);
    }

    public function cambiarContrasena(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('contrasena', [
            'actual' => ['required', 'string', 'current_password'],
            'nueva' => ['required', 'string', 'confirmed', 'different:actual', new ContrasenaSegura],
        ], [
            'actual.current_password' => 'Tu contraseña actual no es correcta.',
            'nueva.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
            'nueva.different' => 'La nueva contraseña debe ser distinta a la actual.',
        ]);

        $user->forceFill(['password' => Hash::make($data['nueva']), 'remember_token' => Str::random(60)])->save();
        /* Por seguridad, cualquier otro dispositivo con la sesión abierta debe volver a entrar. */
        Auth::logoutOtherDevices($data['nueva']);
        $request->session()->regenerate();

        return back()->with('success', 'Cambiamos tu contraseña. Si tenías la sesión abierta en otro dispositivo, tendrás que volver a entrar ahí.');
    }

    public function regenerarCodigos(Request $request): RedirectResponse
    {
        $request->validateWithBag('codigos', ['contrasena' => ['required', 'string', 'current_password']], [
            'contrasena.current_password' => 'Tu contraseña no es correcta.',
        ]);
        abort_unless($request->user()->two_factor_enabled && $request->user()->two_factor_confirmed_at !== null, 422);

        $codigos = $this->dosPasos->regenerarCodigosRespaldo($request->user());

        return back()->with('codigos_respaldo_nuevos', $codigos)->with('success', 'Generamos códigos nuevos. Los anteriores ya no sirven.');
    }

    public function cerrarOtrasSesiones(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('sesiones', ['contrasena' => ['required', 'string', 'current_password']], [
            'contrasena.current_password' => 'Tu contraseña no es correcta.',
        ]);

        Auth::logoutOtherDevices($data['contrasena']);

        return back()->with('success', 'Cerramos tu sesión en todos los demás dispositivos.');
    }
}
