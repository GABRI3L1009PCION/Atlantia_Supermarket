<?php

namespace App\Notifications\Channels;

use App\Jobs\EnviarPush;
use App\Models\PushSuscripcion;
use Illuminate\Notifications\Notification;

/**
 * Canal de notificaciones push: se envia en segundo plano a todos los dispositivos del usuario.
 */
class WebPushChannel
{
    public static function disponiblePara(object $notifiable): bool
    {
        return isset($notifiable->id) && PushSuscripcion::query()->where('user_id', $notifiable->id)->exists();
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        /** @var array{titulo: string, cuerpo: string, url: string, etiqueta?: string}|null $aviso */
        $aviso = $notification->toWebPush($notifiable);

        if ($aviso !== null) {
            EnviarPush::dispatch((int) $notifiable->id, $aviso['titulo'], $aviso['cuerpo'], $aviso['url'], $aviso['etiqueta'] ?? null);
        }
    }
}
