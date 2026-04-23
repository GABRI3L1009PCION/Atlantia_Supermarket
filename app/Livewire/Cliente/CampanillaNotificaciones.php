<?php

namespace App\Livewire\Cliente;

use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\View\View;
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
     * Marca una notificacion especifica como leida.
     */
    public function markAsRead(string $id): void
    {
        app(NotificationService::class)->markAsRead(auth()->user(), [$id]);
    }

    /**
     * Marca todas como leidas.
     */
    public function markAllAsRead(): void
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());
    }

    /**
     * Renderiza el dropdown de notificaciones.
     */
    public function render(): View
    {
        $service = app(NotificationService::class);

        return view('livewire.cliente.campanilla-notificaciones', [
            'notificaciones' => auth()->check() ? $service->latest(auth()->user(), 10) : collect(),
            'noLeidas' => auth()->check() ? $service->unreadCount(auth()->user()) : 0,
        ]);
    }
}
