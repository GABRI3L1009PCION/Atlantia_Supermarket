<?php

namespace App\Services\Repartidores;

use App\Models\CourierSupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Gestiona soporte operativo y emergencias del repartidor.
 */
class CourierSupportService
{
    /**
     * Lista tickets recientes.
     *
     * @return Collection<int, CourierSupportTicket>
     */
    public function recentFor(User $user): Collection
    {
        return CourierSupportTicket::query()
            ->with(['pedido', 'externalOrder'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Crea ticket de soporte.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): CourierSupportTicket
    {
        return CourierSupportTicket::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'pedido_id' => $data['pedido_id'] ?? null,
            'external_delivery_order_id' => $data['external_delivery_order_id'] ?? null,
            'type' => $data['type'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'message' => $data['message'],
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Crea ticket prioritario de emergencia.
     *
     * @param  array<string, mixed>  $data
     */
    public function emergency(User $user, array $data): CourierSupportTicket
    {
        return $this->create($user, [
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
    }
}
