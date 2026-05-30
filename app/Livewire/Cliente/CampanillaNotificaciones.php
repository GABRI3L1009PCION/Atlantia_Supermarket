<?php

namespace App\Livewire\Cliente;

use App\Models\Vendor;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Campanilla de notificaciones in-app para usuarios autenticados.
 */
class CampanillaNotificaciones extends Component
{
    /**
     * Indica si el dropdown esta abierto.
     */
    public bool $open = false;

    /**
     * Alterna visibilidad del panel.
     */
    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    /**
     * Cierra el dropdown de notificaciones.
     */
    public function close(): void
    {
        $this->open = false;
    }

    /**
     * Marca una notificacion especifica como leida.
     */
    public function markAsRead(string $id): void
    {
        app(NotificationService::class)->markAsRead(auth()->user(), [$id]);
        $this->open = false;
    }

    /**
     * Marca todas como leidas.
     */
    public function markAllAsRead(): void
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());
        $this->open = false;
    }

    /**
     * Renderiza el dropdown de notificaciones.
     */
    public function render(): View
    {
        $service = app(NotificationService::class);
        $user = auth()->user();
        $notificaciones = $user ? $service->latest($user, 10) : collect();
        $noLeidas = $user ? $service->unreadCount($user) : 0;
        $vendedoresPendientes = $this->pendingVendorCount();

        if ($vendedoresPendientes > 0) {
            $notificaciones = $this->prependPendingVendorNotification($notificaciones, $vendedoresPendientes);
        }

        return view('livewire.cliente.campanilla-notificaciones', [
            'notificaciones' => $notificaciones,
            'noLeidas' => $noLeidas,
        ]);
    }

    /**
     * Cuenta solicitudes pendientes visibles para administradores.
     */
    private function pendingVendorCount(): int
    {
        $user = auth()->user();

        if (! $user?->hasAnyRole(['admin', 'super_admin'])) {
            return 0;
        }

        return Vendor::query()->pending()->count();
    }

    /**
     * Agrega una notificacion operativa sintetica para solicitudes de vendedores.
     *
     * @param Collection<int, object> $notifications
     * @return Collection<int, object>
     */
    private function prependPendingVendorNotification(Collection $notifications, int $count): Collection
    {
        $notification = (object) [
            'id' => 'vendor-pending-requests',
            'data' => [
                'title' => 'Solicitudes de vendedores pendientes',
                'message' => $count === 1
                    ? 'Hay 1 solicitud esperando revision administrativa.'
                    : "Hay {$count} solicitudes esperando revision administrativa.",
                'url' => route('admin.vendedores.index', ['status' => 'pending']),
            ],
            'read_at' => now(),
            'created_at' => now(),
        ];

        return $notifications->prepend($notification)->take(10)->values();
    }
}
