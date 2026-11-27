<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\Contacto\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bandeja del administrador: mensajes de contacto, cotizaciones de planes y ayuda a tiendas.
 */
class MensajeController extends Controller
{
    public function __construct(private readonly ContactMessageService $mensajes) {}

    public function index(Request $request): View
    {
        $tipo = in_array($request->query('tipo'), ['contacto', 'cotizacion', 'soporte'], true) ? $request->query('tipo') : '';
        $estado = in_array($request->query('estado'), ['pendientes', 'atendidos', 'todos'], true) ? $request->query('estado') : 'pendientes';

        $pendientes = ContactMessage::query()->where('atendido', false)
            ->selectRaw('tipo, count(*) as total')->groupBy('tipo')->pluck('total', 'tipo');

        return view('admin.mensajes.index', [
            'tipo' => $tipo,
            'estado' => $estado,
            'pendientes' => $pendientes,
            'categorias' => ContactMessage::CATEGORIAS_SOPORTE + \App\Http\Controllers\ContactoController::TEMAS,
            'mensajes' => $this->mensajes->paginate([
                'tipo' => $tipo,
                'atendido' => match ($estado) {
                    'pendientes' => '0',
                    'atendidos' => '1',
                    default => '',
                },
            ]),
        ]);
    }

    public function atender(Request $request, ContactMessage $mensaje): RedirectResponse
    {
        $data = $request->validate([
            'respuesta' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($mensaje->atendido) {
            return back()->with('success', 'Ese mensaje ya estaba atendido.');
        }

        $this->mensajes->respond($mensaje, $data, $request->user());

        return back()->with('success', filled($data['respuesta'] ?? null) && $mensaje->user_id
            ? 'Mensaje atendido. Le avisamos a '.$mensaje->nombre.' en su panel.'
            : 'Mensaje marcado como atendido.');
    }
}
