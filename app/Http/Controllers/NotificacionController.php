<?php

namespace App\Http\Controllers;

use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Gestiona las notificaciones in-app del usuario autenticado.
 */
class NotificacionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly NotificationService $notificationService) {}

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
     * Últimas notificaciones en JSON para la campana y los avisos emergentes del panel.
     */
    public function recientes(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'no_leidas' => $this->notificationService->unreadCount($user),
            'items' => $this->notificationService->latest($user, 8)->map(fn (object $item): array => [
                'id' => (string) $item->id,
                'titulo' => (string) ($item->data['title'] ?? 'Notificación'),
                'mensaje' => (string) ($item->data['message'] ?? ''),
                'url' => route('notificaciones.open', $item->id),
                'leida' => $item->read_at !== null,
                'hace' => Carbon::parse($item->created_at)->diffForHumans(),
            ])->values(),
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

        $destino = $this->rutaInterna($item?->data['url'] ?? null);

        return $destino !== null
            ? redirect()->to($destino)
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
     * Convierte el destino guardado en una ruta interna (solo ruta y parámetros).
     *
     * Las notificaciones pueden guardar la URL completa con el dominio con que se
     * generaron (otra IP, APP_URL distinta). Se descarta el dominio y se usa solo la
     * ruta, así abre siempre dentro de este sitio y nunca redirige a un sitio externo.
     */
    private function rutaInterna(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $partes = parse_url($url);

        if ($partes === false) {
            return null;
        }

        $ruta = $partes['path'] ?? '';

        /* "//dominio" o rutas vacías no son destinos internos válidos. */
        if ($ruta === '' || ! str_starts_with($ruta, '/') || str_starts_with($ruta, '//')) {
            return null;
        }

        return $ruta.(isset($partes['query']) ? '?'.$partes['query'] : '');
    }
}
