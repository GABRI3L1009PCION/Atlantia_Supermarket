<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API de notificaciones internas.
 */
class NotificacionApiController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * Lista notificaciones del usuario.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Notificaciones obtenidas.',
            'data' => $this->notificationService->forUser($request->user()),
        ]);
    }

    /**
     * Marca notificaciones como leidas.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $ids = is_array($request->input('ids')) ? $request->input('ids') : [];
        $this->notificationService->markAsRead($request->user(), $ids);

        return response()->json(['message' => 'Notificaciones marcadas como leidas.', 'data' => []]);
    }
}
