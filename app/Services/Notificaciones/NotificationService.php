<?php

namespace App\Services\Notificaciones;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lectura de notificaciones internas.
 */
class NotificationService
{
    /**
     * Lista notificaciones recientes del usuario.
     *
     * @param User $user
     * @return Collection<int, object>
     */
    public function forUser(User $user): Collection
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Marca notificaciones especificas como leidas.
     *
     * @param User $user
     * @param array<int, string> $ids
     * @return int
     */
    public function markAsRead(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    /**
     * Crea una notificacion interna compatible con la tabla Laravel.
     *
     * @param User $user
     * @param string $type
     * @param array<string, mixed> $data
     * @return string
     */
    public function create(User $user, string $type, array $data): string
    {
        $id = (string) \Illuminate\Support\Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
