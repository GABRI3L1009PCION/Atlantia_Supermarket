<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Avisa dentro del panel que Atlantia respondió un mensaje de ayuda o de cotización.
 */
class MensajeRespondidoNotification extends Notification
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
        $url = match (true) {
            $this->mensaje->tipo === 'soporte' && $notifiable->hasRole('vendedor') => route('vendedor.soporte.index', [], false),
            $this->mensaje->tipo === 'cotizacion' && $notifiable->hasRole('vendedor') => route('vendedor.plan.index', [], false),
            default => null,
        };

        return [
            'title' => 'Atlantia respondió tu mensaje',
            'message' => '«'.Str::limit($this->mensaje->asunto, 60).'»: '.Str::limit((string) $this->mensaje->respuesta, 140),
            'url' => $url,
            'mensaje_uuid' => $this->mensaje->uuid,
            'variant' => 'support',
        ];
    }
}
