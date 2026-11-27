<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Exceptions\PagoRechazadoException;
use App\Http\Requests\Cliente\Perfil\UpdatePerfilRequest;
use App\Models\Cliente\ClienteDetalle;
use App\Models\PushSuscripcion;
use App\Services\Clientes\EliminarCuentaService;
use App\Services\Clientes\PerfilClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador de perfil del cliente.
 */
class PerfilController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PerfilClienteService $perfilClienteService) {}

    /**
     * Muestra el perfil del cliente.
     */
    public function edit(Request $request): View
    {
        $this->authorize('update', $request->user());

        $user = $request->user();

        return view('cliente.perfil.edit', [
            'perfil' => $this->perfilClienteService->detail($user),
            'aceptaOfertas' => (ClienteDetalle::query()->where('user_id', $user->id)->first()?->preferencias['ofertas'] ?? true) !== false,
            'avisosEnEsteNavegador' => PushSuscripcion::query()->where('user_id', $user->id)->count(),
            'impedimentoEliminar' => app(EliminarCuentaService::class)->impedimento($user),
        ]);
    }

    /**
     * Avisos de ofertas y novedades: el cliente decide si los recibe.
     */
    public function avisos(Request $request): RedirectResponse
    {
        $user = $request->user();
        $acepta = $request->boolean('ofertas');

        $detalle = ClienteDetalle::query()->firstOrNew(['user_id' => $user->id]);
        $detalle->preferencias = array_replace((array) $detalle->preferencias, ['ofertas' => $acepta]);
        $detalle->save();
        PushSuscripcion::query()->where('user_id', $user->id)->update(['ofertas' => $acepta]);

        return back()->with('success', $acepta
            ? 'Listo: te avisaremos de ofertas y novedades.'
            : 'Listo: ya no te enviaremos avisos de ofertas. Los avisos de tus pedidos siguen llegando.');
    }

    /**
     * El cliente elimina su cuenta y sus datos personales.
     */
    public function destroy(Request $request, EliminarCuentaService $eliminar): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('cliente') && ! $user->isAdministrator() && ! $user->hasAnyRole(['vendedor', 'repartidor']), 403);

        $request->validateWithBag('eliminar', [
            'contrasena' => ['required', 'string', 'current_password'],
            'confirmo' => ['accepted'],
        ], [
            'contrasena.required' => 'Escribe tu contraseña para confirmar.',
            'contrasena.current_password' => 'Tu contraseña no es correcta.',
            'confirmo.accepted' => 'Marca la casilla para confirmar que entiendes que no se puede deshacer.',
        ]);

        if (($impedimento = $eliminar->impedimento($user)) !== null) {
            return back()->withErrors(['contrasena' => $impedimento], 'eliminar');
        }

        try {
            $eliminar->eliminar($user);
        } catch (PagoRechazadoException $exception) {
            return back()->withErrors(['contrasena' => $exception->publicMessage()], 'eliminar');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Eliminamos tu cuenta y tus datos personales. Gracias por haber comprado en Atlantia.');
    }

    /**
     * Actualiza el perfil del cliente.
     */
    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());
        $this->perfilClienteService->update($request->user(), $request->validated());

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
