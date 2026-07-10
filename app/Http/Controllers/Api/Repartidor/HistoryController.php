<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct(private readonly MobileRepartidorPayloadService $payload) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $internal = DeliveryRoute::query()
            ->with(['pedido.cliente', 'pedido.direccion', 'pedido.vendor', 'pedido.items', 'pedido.deliveryRoute'])
            ->where('repartidor_id', $user->id)
            ->whereIn('estado', ['completada', 'cancelada'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (DeliveryRoute $route): array => [
                'type' => 'internal',
                'completed_at' => $route->completada_at?->toIso8601String(),
                'route' => $this->payload->route($route),
                'order' => $route->pedido ? $this->payload->internalOrder($route->pedido) : null,
            ]);
        $external = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (ExternalDeliveryOrder $order): array => [
                'type' => 'external',
                'completed_at' => $order->delivered_at?->toIso8601String(),
                'order' => $this->payload->externalOrder($order),
            ]);

        return response()->json([
            'message' => 'Historial obtenido.',
            'data' => $internal->concat($external)
                ->sortByDesc('completed_at')
                ->values()
                ->take(40),
        ]);
    }
}
