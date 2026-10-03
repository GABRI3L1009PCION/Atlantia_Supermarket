<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Exceptions\TransaccionFallidaException;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Pedidos\PedidoRepartidorService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function __construct(
        private readonly PedidoRepartidorService $pedidoService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAssignedOrders', Pedido::class);

        return response()->json([
            'message' => 'Pedidos obtenidos.',
            'data' => $this->pedidoService->assigned($request->user())
                ->map(fn (Pedido $pedido): array => $this->payload->internalOrder($pedido))
                ->values(),
        ]);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        $this->authorize('viewAssigned', $pedido);

        return response()->json([
            'message' => 'Pedido obtenido.',
            'data' => $this->payload->internalOrder($this->pedidoService->detail($pedido)),
        ]);
    }

    public function accept(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);

        return $this->respondOrder(function () use ($pedido, $request): Pedido {
            return $this->pedidoService->accept($pedido, $request->user());
        }, 'Entrega aceptada.');
    }

    public function reject(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        return $this->respondOrder(function () use ($pedido, $request, $data): Pedido {
            return $this->pedidoService->reject($pedido, $data, $request->user());
        }, 'Entrega rechazada.');
    }

    public function arrivedPickup(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);

        return $this->respondOrder(function () use ($pedido, $request): Pedido {
            return $this->pedidoService->arrivedPickup($pedido, $request->user());
        }, 'Llegada al establecimiento registrada.');
    }

    public function pickupNotReady(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $data = $request->validate(['pickup_issue_reason' => ['nullable', 'string', 'max:255']]);

        return $this->respondOrder(function () use ($pedido, $request, $data): Pedido {
            return $this->pedidoService->pickupNotReady($pedido, $request->user(), $data['pickup_issue_reason'] ?? null);
        }, 'Reporte de pedido no listo enviado.');
    }

    public function pickup(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $data = $request->validate([
            'latitude_inicio' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_inicio' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        return $this->respondOrder(function () use ($pedido, $request, $data): Pedido {
            return $this->pedidoService->pickup($pedido, $request->user(), $data);
        }, 'Pedido recogido.');
    }

    public function arrivedCustomer(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);

        return $this->respondOrder(function () use ($pedido, $request): Pedido {
            return $this->pedidoService->arrivedCustomer($pedido, $request->user());
        }, 'Llegada al cliente registrada.');
    }

    public function verifyCode(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $data = $request->validate([
            'confirmation_code' => ['required', 'digits:4'],
        ]);

        return $this->respondOrder(function () use ($pedido, $request, $data): Pedido {
            return $this->pedidoService->verifyDeliveryCode($pedido, (string) $data['confirmation_code'], $request->user());
        }, 'Codigo verificado.');
    }

    public function deliver(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('updateDeliveryStatus', $pedido);
        $data = $request->validate([
            'confirmation_code' => ['nullable', 'digits:4'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'foto_entrega' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        return $this->respondOrder(function () use ($pedido, $request, $data): Pedido {
            return $this->pedidoService->deliver($pedido, $data, $request->user());
        }, 'Pedido entregado.');
    }

    public function acknowledgeCompletion(Pedido $pedido, Request $request): JsonResponse
    {
        $this->authorize('viewAssigned', $pedido);

        return $this->respondOrder(function () use ($pedido, $request): Pedido {
            return $this->pedidoService->acknowledgeCompletion($pedido, $request->user());
        }, 'Cierre de entrega confirmado.');
    }

    /**
     * @param  callable(): Pedido  $callback
     */
    private function respondOrder(callable $callback, string $message): JsonResponse
    {
        try {
            $pedido = $callback();
        } catch (TransaccionFallidaException $exception) {
            return response()->json(['message' => $exception->publicMessage()], 422);
        }

        return response()->json([
            'message' => $message,
            'data' => $this->payload->internalOrder($pedido),
        ]);
    }
}
