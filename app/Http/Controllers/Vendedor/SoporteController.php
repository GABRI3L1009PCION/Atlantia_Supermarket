<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\SoporteVendedorNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Ayuda y soporte: la tienda escribe a Atlantia sin salir del panel y ve las respuestas.
 */
class SoporteController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);

        return view('vendedor.soporte.index', [
            'vendor' => $vendor,
            'categorias' => ContactMessage::CATEGORIAS_SOPORTE,
            'mensajes' => ContactMessage::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('tipo', ['soporte', 'cotizacion'])
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'categoria' => ['required', Rule::in(array_keys(ContactMessage::CATEGORIAS_SOPORTE))],
            'asunto' => ['required', 'string', 'min:4', 'max:120'],
            'mensaje' => ['required', 'string', 'min:10', 'max:2000'],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\s\-]{8,20}$/'],
            'urgente' => ['nullable', 'boolean'],
        ], [
            'categoria.required' => 'Elige de qué se trata.',
            'asunto.required' => 'Escribe un asunto corto.',
            'asunto.min' => 'El asunto es muy corto.',
            'mensaje.required' => 'Cuéntanos qué pasó.',
            'mensaje.min' => 'Cuéntanos un poco más para poder ayudarte.',
            'telefono.regex' => 'Escribe un teléfono válido, por ejemplo 5555 1234.',
        ]);

        $abiertos = ContactMessage::query()->where('user_id', $request->user()->id)->where('tipo', 'soporte')->where('atendido', false)->count();
        if ($abiertos >= 5) {
            return back()->withInput()->with('error', 'Ya tienes 5 mensajes sin responder. Espera nuestra respuesta antes de enviar otro.');
        }

        $mensaje = ContactMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'tipo' => 'soporte',
            'categoria' => $data['categoria'],
            'nombre' => $vendor->business_name,
            'email' => $request->user()->email,
            'telefono' => $data['telefono'] ?? $request->user()->phone ?? null,
            'asunto' => trim($data['asunto']),
            'mensaje' => trim($data['mensaje']),
            'prioridad' => $request->boolean('urgente') ? 'alta' : 'normal',
            'atendido' => false,
        ]);

        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
            if ($roles !== []) {
                Notification::send(User::query()->role($roles)->get(), new SoporteVendedorNotification($mensaje, $vendor));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('vendedor.soporte.index')->with('success', 'Recibimos tu mensaje. Te responderemos aquí mismo y te avisaremos en tus notificaciones.');
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
