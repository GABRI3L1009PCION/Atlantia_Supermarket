<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\MensajeContacto\RespondContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\Contacto\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de atencion de mensajes de contacto.
 */
class MensajeContactoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ContactMessageService $contactMessageService)
    {
    }

    /**
     * Lista mensajes de contacto.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactMessage::class);

        return view('empleado.contacto.index', ['messages' => $this->contactMessageService->paginate($request->all())]);
    }

    /**
     * Responde o marca como atendido un mensaje.
     */
    public function respond(RespondContactMessageRequest $request, ContactMessage $message): RedirectResponse
    {
        $this->authorize('respond', $message);
        $this->contactMessageService->respond($message, $request->validated(), $request->user());

        return back()->with('success', 'Mensaje atendido correctamente.');
    }
}
