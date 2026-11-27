<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Avisa a la administración que llegó un mensaje desde la página de contacto.
 */
class NuevoMensajeContactoNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ContactMessage $mensaje) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nuevo mensaje de contacto',
            'message' => $this->mensaje->nombre.': '.Str::limit($this->mensaje->asunto, 100),
            'url' => route('admin.mensajes.index', ['tipo' => 'contacto'], false),
            'mensaje_uuid' => $this->mensaje->uuid,
            'variant' => 'support',
        ];
    }
}
