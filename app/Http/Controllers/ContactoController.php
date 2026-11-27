<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NuevoMensajeContactoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Formulario público de contacto: el mensaje llega a la bandeja "Mensajes" del administrador.
 */
class ContactoController extends Controller
{
    public const TEMAS = [
        'pedido' => 'Un pedido',
        'pago' => 'Pagos o reembolsos',
        'cuenta' => 'Mi cuenta',
        'vender' => 'Quiero vender en Atlantia',
        'sugerencia' => 'Sugerencia o queja',
        'otro' => 'Otro tema',
    ];

    public function store(Request $request): RedirectResponse
    {
        /* Campo oculto: si un robot lo llena, fingimos que se envió y no guardamos nada. */
        if (filled($request->input('sitio_web'))) {
            return redirect()->route('contacto')->withFragment('escribenos')->with('success', 'Recibimos tu mensaje. Te responderemos pronto.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\s\-]{8,20}$/'],
            'tema' => ['required', Rule::in(array_keys(self::TEMAS))],
            'mensaje' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'nombre.required' => 'Escribe tu nombre.',
            'email.required' => 'Escribe tu correo para poder responderte.',
            'email.email' => 'Escribe un correo válido.',
            'telefono.regex' => 'Escribe un teléfono válido, por ejemplo 5555 1234.',
            'tema.required' => 'Elige de qué se trata.',
            'mensaje.required' => 'Cuéntanos en qué te ayudamos.',
            'mensaje.min' => 'Cuéntanos un poco más para poder ayudarte.',
        ]);

        $mensaje = ContactMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()?->id,
            'tipo' => 'contacto',
            'categoria' => $data['tema'],
            'nombre' => trim($data['nombre']),
            'email' => Str::lower(trim($data['email'])),
            'telefono' => $data['telefono'] ?? null,
            'asunto' => self::TEMAS[$data['tema']].' · '.Str::limit(trim(preg_replace('/\s+/u', ' ', $data['mensaje'])), 70),
            'mensaje' => trim($data['mensaje']),
            'prioridad' => in_array($data['tema'], ['pedido', 'pago'], true) ? 'alta' : 'normal',
            'atendido' => false,
        ]);

        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
            if ($roles !== []) {
                Notification::send(User::query()->role($roles)->get(), new NuevoMensajeContactoNotification($mensaje));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('contacto')->withFragment('escribenos')
            ->with('success', 'Recibimos tu mensaje. Te responderemos a '.$mensaje->email.' lo antes posible.');
    }
}
