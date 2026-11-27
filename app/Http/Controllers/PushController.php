<?php

namespace App\Http\Controllers;

use App\Models\Cliente\ClienteDetalle;
use App\Models\Pedido;
use App\Models\PushSuscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Registra o quita el dispositivo del cliente para notificaciones push.
 */
class PushController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'ofertas' => ['nullable', 'boolean'],
            'pedido_uuid' => ['nullable', 'uuid'],
        ]);

        $userId = $request->user()?->id ?? $this->clienteDePedidoInvitado($request, $data['pedido_uuid'] ?? null);

        PushSuscripcion::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $userId,
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                /* Si el cliente apagó los avisos de ofertas en su perfil, se respeta en cada navegador nuevo. */
                'ofertas' => $request->boolean('ofertas', true)
                    && ($userId === null || (ClienteDetalle::query()->where('user_id', $userId)->first()?->preferencias['ofertas'] ?? true) !== false),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2000']]);
        PushSuscripcion::query()->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * El invitado solo puede vincular su dispositivo a un pedido que hizo en este navegador.
     */
    private function clienteDePedidoInvitado(Request $request, ?string $pedidoUuid): ?int
    {
        if ($pedidoUuid === null || ! in_array($pedidoUuid, $request->session()->get('guest_order_uuids', []), true)) {
            return null;
        }

        return Pedido::query()->where('uuid', $pedidoUuid)->value('cliente_id');
    }
}
