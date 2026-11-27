<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Rules\ContrasenaSegura;
use App\Services\Auth\LoginService;
use App\Services\Vendedores\AccesoVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Primer acceso del vendedor aprobado: crea su contraseña desde el enlace del correo.
 */
class CrearAccesoController extends Controller
{
    public function __construct(
        private readonly AccesoVendedorService $acceso,
        private readonly LoginService $loginService,
    ) {}

    public function create(Request $request, string $usuario): View
    {
        $user = $this->usuarioDelEnlace($request, $usuario);

        if ($user === null) {
            return view('vendedor.acceso.vencido');
        }

        return view('vendedor.acceso.crear', [
            'user' => $user,
            'vendor' => Vendor::query()->where('user_id', $user->id)->first(),
            'accion' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, string $usuario): RedirectResponse|View
    {
        $user = $this->usuarioDelEnlace($request, $usuario);

        if ($user === null) {
            return view('vendedor.acceso.vencido');
        }

        $request->validate([
            'password' => ['required', 'confirmed', new ContrasenaSegura],
        ], [
            'password.required' => 'Escribe tu nueva contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ]);

        /* Al cambiar la contraseña, la huella del enlace deja de coincidir: el enlace ya no sirve. */
        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => 'vendedor.contrasena_creada',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'metadata' => ['origen' => 'enlace_de_aprobacion'],
            'method' => 'SERVICE',
        ]);

        Auth::logout();

        if ($this->loginService->requiereDosFactores($user)) {
            $ruta = $this->loginService->iniciarDesafioDosFactores($user, $request);

            return redirect()->route($ruta)->with('success', 'Contraseña creada. Ahora protege tu cuenta con Google Authenticator.');
        }

        return redirect()->route('login')->with('success', 'Contraseña creada. Ya puedes iniciar sesión.');
    }

    /**
     * Usuario del enlace si la firma, la vigencia y la huella siguen siendo válidas.
     */
    private function usuarioDelEnlace(Request $request, string $usuario): ?User
    {
        if (! $request->hasValidSignature()) {
            return null;
        }

        $user = User::query()->where('uuid', $usuario)->where('status', 'active')->first();

        if ($user === null || ! $this->acceso->huellaValida($user, (string) $request->query('huella'))) {
            return null;
        }

        return Vendor::query()->where('user_id', $user->id)->where('status', 'approved')->exists() ? $user : null;
    }
}
