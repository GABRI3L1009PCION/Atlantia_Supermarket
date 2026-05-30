<?php

namespace App\Http\Controllers;

use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestiona las notificaciones in-app del usuario autenticado.
 */
class NotificacionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * Muestra todas las notificaciones recientes.
     */
    public function index(Request $request): View
    {
        return view('notificaciones.index', [
            'notificaciones' => $this->notificationService->forUser($request->user(), 50),
            'noLeidas' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    /**
     * Marca una notificacion como leida y abre su destino, si existe.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        $item = $this->notificationService->findForUser($user, $notification);

        if ($item !== null) {
            $this->notificationService->markAsRead($user, [$notification]);
        }

        $url = $item?->data['url'] ?? null;

        return is_string($url) && $this->isInternalUrl($url)
            ? redirect()->to($url)
            : redirect()->route('notificaciones.index');
    }

    /**
     * Marca una notificacion como leida.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $this->notificationService->markAsRead($request->user(), [$notification]);

        return back();
    }

    /**
     * Marca todas las notificaciones del usuario como leidas.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return back();
    }

    /**
     * Evita abrir destinos externos desde datos guardados en notificaciones.
     */
    private function isInternalUrl(string $url): bool
    {
        return $url !== '' && (str_starts_with($url, '/') || str_starts_with($url, url('/')));
    }
}
