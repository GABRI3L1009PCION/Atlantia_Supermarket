<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Exceptions\TransaccionFallidaException;
use App\Http\Controllers\Controller;
use App\Models\ExternalDeliveryOrder;
use App\Services\Repartidores\ExternalDeliveryOrderService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalDeliveryController extends Controller
{
    public function __construct(
        private readonly ExternalDeliveryOrderService $externalService,
        private readonly MobileRepartidorPayloadService $payload
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Entregas externas obtenidas.',
            'data' => $this->externalService->assignedTo($request->user())
                ->map(fn (ExternalDeliveryOrder $order): array => $this->payload->externalOrder($order))
                ->values(),
        ]);
    }

    public function show(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        abort_unless((int) $externalDeliveryOrder->repartidor_id === (int) $request->user()->id, 403);

        return response()->json([
            'message' => 'Entrega externa obtenida.',
            'data' => $this->payload->externalOrder($this->externalService->detail($externalDeliveryOrder)),
        ]);
    }

    public function arrivedPickup(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        return $this->respondExternal(function () use ($externalDeliveryOrder, $request): ExternalDeliveryOrder {
            return $this->externalService->arrivedPickup($externalDeliveryOrder, $request->user());
        }, 'Llegada a tienda registrada.');
    }

    public function pickupNotReady(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        $data = $request->validate(['issue_reason' => ['nullable', 'string', 'max:255']]);

        return $this->respondExternal(function () use ($externalDeliveryOrder, $request, $data): ExternalDeliveryOrder {
            return $this->externalService->pickupNotReady($externalDeliveryOrder, $request->user(), $data['issue_reason'] ?? null);
        }, 'Reporte de tienda enviado.');
    }

    public function pickedUp(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        return $this->respondExternal(function () use ($externalDeliveryOrder, $request): ExternalDeliveryOrder {
            return $this->externalService->pickedUp($externalDeliveryOrder, $request->user());
        }, 'Pedido externo recogido.');
    }

    public function arrivedCustomer(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        return $this->respondExternal(function () use ($externalDeliveryOrder, $request): ExternalDeliveryOrder {
            return $this->externalService->arrivedCustomer($externalDeliveryOrder, $request->user());
        }, 'Llegada al cliente registrada.');
    }

    public function verifyCode(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirmation_code' => ['required', 'digits:4'],
        ]);

        return $this->respondExternal(function () use ($externalDeliveryOrder, $request, $data): ExternalDeliveryOrder {
            return $this->externalService->verifyDeliveryCode(
                $externalDeliveryOrder,
                $request->user(),
                (string) $data['confirmation_code']
            );
        }, 'Codigo verificado.');
    }

    public function cashIssue(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        $data = $request->validate(['cash_notes' => ['nullable', 'string', 'max:1000']]);

        return $this->respondExternal(function () use ($externalDeliveryOrder, $request, $data): ExternalDeliveryOrder {
            return $this->externalService->reportCashIssue($externalDeliveryOrder, $request->user(), $data['cash_notes'] ?? null);
        }, 'Reporte de efectivo enviado.');
    }

    public function deliver(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirmation_code' => ['nullable', 'digits:4'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        return $this->respondExternal(function () use ($externalDeliveryOrder, $request, $data): ExternalDeliveryOrder {
            return $this->externalService->deliver($externalDeliveryOrder, $request->user(), $data);
        }, 'Entrega externa completada.');
    }

    public function acknowledgeCompletion(ExternalDeliveryOrder $externalDeliveryOrder, Request $request): JsonResponse
    {
        return $this->respondExternal(function () use ($externalDeliveryOrder, $request): ExternalDeliveryOrder {
            return $this->externalService->acknowledgeCompletion($externalDeliveryOrder, $request->user());
        }, 'Cierre de entrega confirmado.');
    }

    /**
     * @param  callable(): ExternalDeliveryOrder  $callback
     */
    private function respondExternal(callable $callback, string $message): JsonResponse
    {
        try {
            $order = $callback();
        } catch (TransaccionFallidaException $exception) {
            return response()->json(['message' => $exception->publicMessage()], 422);
        }

        return response()->json([
            'message' => $message,
            'data' => $this->payload->externalOrder($order),
        ]);
    }
}
