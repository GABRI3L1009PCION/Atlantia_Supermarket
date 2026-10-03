<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Models\CourierSupportTicket;
use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Services\Repartidores\CourierSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportController extends Controller
{
    public function __construct(private readonly CourierSupportService $supportService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Soporte obtenido.',
            'data' => [
                'center' => $this->supportService->supportCenter(),
                'tickets' => $this->supportService->recentFor($request->user())
                    ->map(fn (CourierSupportTicket $ticket): array => $this->ticket($ticket))
                    ->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pedido_id' => ['nullable', 'integer', 'exists:pedidos,id'],
            'pedido_uuid' => ['nullable', 'uuid'],
            'external_delivery_order_id' => ['nullable', 'integer', 'exists:external_delivery_orders,id'],
            'external_delivery_order_uuid' => ['nullable', 'uuid'],
            'type' => ['required', Rule::in([
                'support_chat',
                'closed_business',
                'customer_problem',
                'store_problem',
                'damaged_order',
                'incomplete_order',
                'payment_problem',
                'forgotten_item',
                'accident',
                'insurance',
            ])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'channel' => ['nullable', Rule::in(['app', 'whatsapp', 'phone', 'emergency'])],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $data = $this->normalizeReferences($request, $data);
        $ticket = $this->supportService->create($request->user(), $data);

        return response()->json([
            'message' => 'Caso enviado a soporte local.',
            'data' => $this->ticket($ticket),
        ], 201);
    }

    public function emergency(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pedido_id' => ['nullable', 'integer', 'exists:pedidos,id'],
            'pedido_uuid' => ['nullable', 'uuid'],
            'external_delivery_order_id' => ['nullable', 'integer', 'exists:external_delivery_orders,id'],
            'external_delivery_order_uuid' => ['nullable', 'uuid'],
            'message' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data = $this->normalizeReferences($request, $data);
        $ticket = $this->supportService->emergency($request->user(), $data);

        return response()->json([
            'message' => 'Emergencia reportada.',
            'data' => $this->ticket($ticket),
        ], 201);
    }

    public function reply(CourierSupportTicket $ticket, Request $request): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(404);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $ticket = $this->supportService->replyAsCourier($ticket, $request->user(), $data);

        return response()->json([
            'message' => 'Mensaje enviado a soporte.',
            'data' => $this->ticket($ticket),
        ]);
    }

    public function updateStatus(CourierSupportTicket $ticket, Request $request): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['closed'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $ticket = $this->supportService->updateStatus($ticket, $data['status'], $data['note'] ?? null, $request->user());

        return response()->json([
            'message' => 'Caso actualizado.',
            'data' => $this->ticket($ticket),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ticket(CourierSupportTicket $ticket): array
    {
        return [
            'id' => $ticket->uuid,
            'type' => $ticket->type,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'channel' => $ticket->channel,
            'message' => $ticket->message,
            'support_response' => $ticket->support_response,
            'assigned_to' => $ticket->assignedTo?->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'last_message_at' => $ticket->last_message_at?->toIso8601String(),
            'messages' => $ticket->messages->map(fn ($message): array => [
                'sender_type' => $message->sender_type,
                'sender_name' => $message->user?->name,
                'message' => $message->message,
                'is_internal' => (bool) $message->is_internal,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeReferences(Request $request, array $data): array
    {
        if (($data['pedido_uuid'] ?? null) !== null) {
            $data['pedido_id'] = Pedido::query()
                ->where('uuid', $data['pedido_uuid'])
                ->whereHas('deliveryRoute', fn ($query) => $query->where('repartidor_id', $request->user()->id))
                ->value('id');

            if ($data['pedido_id'] === null) {
                throw ValidationException::withMessages([
                    'pedido_uuid' => 'El pedido no esta asignado a tu cuenta.',
                ]);
            }
        }

        if (($data['pedido_id'] ?? null) !== null && ($data['pedido_uuid'] ?? null) === null) {
            $assigned = Pedido::query()
                ->whereKey($data['pedido_id'])
                ->whereHas('deliveryRoute', fn ($query) => $query->where('repartidor_id', $request->user()->id))
                ->exists();

            if (! $assigned) {
                throw ValidationException::withMessages([
                    'pedido_id' => 'El pedido no esta asignado a tu cuenta.',
                ]);
            }
        }

        if (($data['external_delivery_order_uuid'] ?? null) !== null) {
            $data['external_delivery_order_id'] = ExternalDeliveryOrder::query()
                ->where('uuid', $data['external_delivery_order_uuid'])
                ->where('repartidor_id', $request->user()->id)
                ->value('id');

            if ($data['external_delivery_order_id'] === null) {
                throw ValidationException::withMessages([
                    'external_delivery_order_uuid' => 'La entrega externa no esta asignada a tu cuenta.',
                ]);
            }
        }

        if (($data['external_delivery_order_id'] ?? null) !== null && ($data['external_delivery_order_uuid'] ?? null) === null) {
            $assigned = ExternalDeliveryOrder::query()
                ->whereKey($data['external_delivery_order_id'])
                ->where('repartidor_id', $request->user()->id)
                ->exists();

            if (! $assigned) {
                throw ValidationException::withMessages([
                    'external_delivery_order_id' => 'La entrega externa no esta asignada a tu cuenta.',
                ]);
            }
        }

        unset($data['pedido_uuid'], $data['external_delivery_order_uuid']);

        return $data;
    }
}
