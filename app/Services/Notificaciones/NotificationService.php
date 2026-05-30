<?php

namespace App\Services\Notificaciones;

use App\Contracts\NotificacionContract;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Servicio de lectura de notificaciones internas.
 */
class NotificationService implements NotificacionContract
{
    /**
     * Lista notificaciones recientes del usuario.
     *
     * @param User $user
     * @return Collection<int, object>
     */
    public function forUser(User $user, int $limit = 50): Collection
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->latest()
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (object $notification): object => $this->decorate($notification, $user));
    }

    /**
     * Obtiene ultimas notificaciones del usuario.
     *
     * @param User $user
     * @param int $limit
     * @return Collection<int, object>
     */
    public function latest(User $user, int $limit = 10): Collection
    {
        return $this->forUser($user, $limit);
    }

    /**
     * Busca una notificacion del usuario.
     *
     * @param User $user
     * @param string $id
     * @return object|null
     */
    public function findForUser(User $user, string $id): ?object
    {
        $notification = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('id', $id)
            ->first();

        return $notification ? $this->decorate($notification, $user) : null;
    }

    /**
     * Cuenta notificaciones no leidas.
     *
     * @param User $user
     * @return int
     */
    public function unreadCount(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
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
     * Marca todas las notificaciones del usuario como leidas.
     *
     * @param User $user
     * @return int
     */
    public function markAllAsRead(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
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

    /**
     * Envia una notificacion interna compatible con el contrato de dominio.
     *
     * @param User $user
     * @param string $tipo
     * @param array<string, mixed> $datos
     * @return string
     */
    public function enviar(User $user, string $tipo, array $datos): string
    {
        return $this->create($user, $tipo, $datos);
    }

    /**
     * Normaliza el payload para vistas y API.
     *
     * @param object $notification
     * @param User $user
     * @return object
     */
    private function decorate(object $notification, User $user): object
    {
        $data = is_array($notification->data ?? null)
            ? $notification->data
            : (json_decode((string) ($notification->data ?? '[]'), true) ?: []);

        $data['title'] = $data['title'] ?? $data['titulo'] ?? $this->titleFromType((string) $notification->type);
        $data['message'] = $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva actualizacion.';
        $data['url'] = $data['url'] ?? $this->resolveUrl((string) $notification->type, $data, $user);

        $notification->data = $data;

        return $notification;
    }

    /**
     * Genera una URL interna cuando la notificacion apunta a un recurso conocido.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $user
     * @return string|null
     */
    private function resolveUrl(string $type, array $data, User $user): ?string
    {
        $pedidoUuid = $data['pedido_uuid'] ?? null;

        if (is_string($pedidoUuid) && $pedidoUuid !== '') {
            if ($user->hasAnyRole(['admin', 'super_admin']) && Route::has('admin.pedidos.show')) {
                return route('admin.pedidos.show', $pedidoUuid);
            }

            if ($user->hasRole('vendedor') && Route::has('vendedor.pedidos.show')) {
                return route('vendedor.pedidos.show', $pedidoUuid);
            }

            if ($user->hasRole('repartidor') && Route::has('repartidor.pedidos.show')) {
                return route('repartidor.pedidos.show', $pedidoUuid);
            }

            if ($user->hasRole('cliente') && Route::has('cliente.pedidos.show')) {
                return route('cliente.pedidos.show', $pedidoUuid);
            }
        }

        $productoUuid = $data['producto_uuid'] ?? null;

        if (is_string($productoUuid) && $productoUuid !== '') {
            if ($user->hasAnyRole(['admin', 'super_admin']) && Route::has('admin.productos.show')) {
                return route('admin.productos.show', $productoUuid);
            }

            if ($user->hasRole('vendedor') && Route::has('vendedor.inventario.index')) {
                return route('vendedor.inventario.index');
            }
        }

        if (str_starts_with($type, 'ml.') && $user->hasAnyRole(['admin', 'super_admin']) && Route::has('admin.ml.monitor')) {
            return route('admin.ml.monitor');
        }

        return null;
    }

    /**
     * Titulo legible de respaldo segun tipo.
     */
    private function titleFromType(string $type): string
    {
        return match (true) {
            str_contains($type, 'pedido') => 'Actualizacion de pedido',
            str_contains($type, 'stock') => 'Producto con stock bajo',
            str_contains($type, 'ml') => 'Alerta operativa ML',
            str_contains($type, 'devolucion') => 'Actualizacion de devolucion',
            default => 'Notificacion',
        };
    }
}
