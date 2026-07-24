<?php

namespace App\Services\Repartidores;

use App\Models\CourierSupportMessage;
use App\Models\CourierSupportTicket;
use App\Models\User;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona soporte operativo y emergencias del repartidor.
 */
class CourierSupportService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Lista tickets recientes.
     *
     * @return Collection<int, CourierSupportTicket>
     */
    public function recentFor(User $user): Collection
    {
        return CourierSupportTicket::query()
            ->with(['pedido', 'externalOrder', 'assignedTo', 'messages.user'])
            ->where('user_id', $user->id)
            ->latest('last_message_at')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function supportCenter(): array
    {
        return [
            'phone' => config('atlantia.support.phone'),
            'emergency_phone' => config('atlantia.support.emergency_phone'),
            'whatsapp' => config('atlantia.support.whatsapp'),
            'average_response_minutes' => (int) config('atlantia.support.average_response_minutes', 2),
            'hours' => config('atlantia.support.hours'),
            'channels' => config('atlantia.support.channels', ['app']),
            'sla_minutes' => config('atlantia.support.sla_minutes', []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardForOperations(): array
    {
        return [
            'open_tickets' => CourierSupportTicket::query()
                ->with(['user', 'assignedTo', 'messages.user'])
                ->whereIn('status', ['open', 'in_progress'])
                ->latest('last_message_at')
                ->limit(30)
                ->get(),
            'emergencies' => CourierSupportTicket::query()
                ->with(['user', 'assignedTo', 'messages.user'])
                ->where('priority', 'critical')
                ->whereIn('status', ['open', 'in_progress'])
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'closed_tickets' => CourierSupportTicket::query()
                ->with(['user', 'assignedTo'])
                ->whereIn('status', ['resolved', 'closed'])
                ->latest('closed_at')
                ->limit(20)
                ->get(),
            'agents' => User::query()
                ->whereHas('roles', fn ($query) => $query
                    ->whereIn('name', ['soporte', 'empleado', 'supervisor_logistica'])
                    ->where('guard_name', 'web'))
                ->active()
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Crea ticket de soporte.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): CourierSupportTicket
    {
        return DB::transaction(function () use ($user, $data): CourierSupportTicket {
            $ticket = CourierSupportTicket::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'pedido_id' => $data['pedido_id'] ?? null,
                'external_delivery_order_id' => $data['external_delivery_order_id'] ?? null,
                'type' => $data['type'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
                'channel' => $data['channel'] ?? 'app',
                'message' => $data['message'],
                'metadata' => $data['metadata'] ?? null,
                'last_message_at' => now(),
            ]);

            $this->appendMessage($ticket, $user, 'courier', $data['message']);
            $this->notifySupportTeam($ticket, 'Nuevo caso de repartidor', $user->name.' envio un nuevo caso a soporte.');

            return $ticket->load(['assignedTo', 'messages.user']);
        });
    }

    /**
     * Crea ticket prioritario de emergencia.
     *
     * @param  array<string, mixed>  $data
     */
    public function emergency(User $user, array $data): CourierSupportTicket
    {
        $ticket = $this->create($user, [
            'pedido_id' => $data['pedido_id'] ?? null,
            'external_delivery_order_id' => $data['external_delivery_order_id'] ?? null,
            'type' => 'emergency',
            'priority' => 'critical',
            'message' => $data['message'] ?? 'Emergencia reportada desde el panel del repartidor.',
            'metadata' => [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'reported_at' => now()->toIso8601String(),
            ],
        ]);

        $this->notifySupportTeam($ticket, 'Emergencia de repartidor', $user->name.' reporto una emergencia operativa.');

        return $ticket;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function replyAsCourier(CourierSupportTicket $ticket, User $user, array $data): CourierSupportTicket
    {
        if ($ticket->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'ticket' => 'El ticket no pertenece a tu cuenta.',
            ]);
        }

        return DB::transaction(function () use ($ticket, $user, $data): CourierSupportTicket {
            if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                $ticket->update([
                    'status' => 'open',
                    'resolved_at' => null,
                    'closed_at' => null,
                ]);
            }

            $this->appendMessage($ticket, $user, 'courier', $data['message']);
            $this->notifySupportTeam($ticket, 'Respuesta del repartidor', $user->name.' agrego un mensaje al caso '.$ticket->uuid.'.');

            return $ticket->refresh()->load(['assignedTo', 'messages.user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function replyFromTeam(CourierSupportTicket $ticket, User $user, array $data): CourierSupportTicket
    {
        return DB::transaction(function () use ($ticket, $user, $data): CourierSupportTicket {
            if ($ticket->assigned_to_user_id === null) {
                $ticket->update(['assigned_to_user_id' => $user->id]);
            }

            $status = $data['status'] ?? 'in_progress';
            $this->appendMessage($ticket, $user, 'support', $data['message'], (bool) ($data['is_internal'] ?? false));
            $this->applyStatus($ticket, $status, $data['message']);

            $this->notificationService->enviar($ticket->user, 'courier.support.reply', [
                'title' => 'Soporte respondio tu caso',
                'message' => $data['message'],
                'url' => route('repartidor.soporte.index'),
            ]);

            return $ticket->refresh()->load(['assignedTo', 'messages.user']);
        });
    }

    public function assign(CourierSupportTicket $ticket, User $assignee, User $actor): CourierSupportTicket
    {
        if (! $assignee->hasAnyRole(['soporte', 'empleado', 'supervisor_logistica'])) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'El usuario seleccionado no puede atender soporte.',
            ]);
        }

        $ticket->update([
            'assigned_to_user_id' => $assignee->id,
            'status' => in_array($ticket->status, ['resolved', 'closed'], true) ? 'open' : 'in_progress',
        ]);

        $this->appendMessage($ticket, $actor, 'system', 'Caso asignado a '.$assignee->name.'.', true);
        $this->notificationService->enviar($assignee, 'courier.support.assigned', [
            'title' => 'Caso asignado',
            'message' => 'Se te asigno el caso '.$ticket->uuid.'.',
            'url' => route('empleado.soporte-repartidores.index'),
        ]);

        return $ticket->refresh()->load(['assignedTo', 'messages.user']);
    }

    public function updateStatus(CourierSupportTicket $ticket, string $status, ?string $note = null, ?User $actor = null): CourierSupportTicket
    {
        $this->applyStatus($ticket, $status, $note);

        if ($note !== null && $note !== '' && $actor !== null) {
            $this->appendMessage($ticket, $actor, 'system', $note, true);
        }

        $this->notificationService->enviar($ticket->user, 'courier.support.status', [
            'title' => 'Estado de soporte actualizado',
            'message' => 'Tu caso ahora esta en estado '.$status.'.',
            'url' => route('repartidor.soporte.index'),
        ]);

        return $ticket->refresh()->load(['assignedTo', 'messages.user']);
    }

    private function appendMessage(
        CourierSupportTicket $ticket,
        ?User $user,
        string $senderType,
        string $message,
        bool $isInternal = false
    ): CourierSupportMessage {
        $entry = $ticket->messages()->create([
            'user_id' => $user?->id,
            'sender_type' => $senderType,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);

        $ticket->update([
            'support_response' => $senderType === 'support' ? $message : $ticket->support_response,
            'last_message_at' => now(),
        ]);

        return $entry;
    }

    private function applyStatus(CourierSupportTicket $ticket, string $status, ?string $supportResponse = null): void
    {
        $payload = [
            'status' => $status,
            'support_response' => $supportResponse ?? $ticket->support_response,
            'last_message_at' => now(),
        ];

        if (in_array($status, ['resolved', 'closed'], true)) {
            $payload['resolved_at'] = $ticket->resolved_at ?? now();
            $payload['closed_at'] = now();
        } else {
            $payload['resolved_at'] = null;
            $payload['closed_at'] = null;
        }

        $ticket->update($payload);
    }

    private function notifySupportTeam(CourierSupportTicket $ticket, string $title, string $message): void
    {
        User::query()
            ->whereHas('roles', fn ($query) => $query
                ->whereIn('name', ['soporte', 'empleado', 'supervisor_logistica'])
                ->where('guard_name', 'web'))
            ->active()
            ->get()
            ->each(function (User $user) use ($ticket, $title, $message): void {
                $this->notificationService->enviar($user, 'courier.support.team', [
                    'title' => $title,
                    'message' => $message,
                    'url' => route('empleado.soporte-repartidores.index'),
                    'ticket_uuid' => $ticket->uuid,
                ]);
            });
    }
}
