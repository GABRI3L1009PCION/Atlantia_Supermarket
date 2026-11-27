<?php

namespace App\Notifications\Channels;

use App\Jobs\EnviarWhatsApp;
use Illuminate\Notifications\Notification;

/**
 * Canal de notificaciones que encola el mensaje de WhatsApp.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        /** @var array{telefono: ?string, plantilla: string, parametros: array<int, string>, texto: string}|null $mensaje */
        $mensaje = $notification->toWhatsApp($notifiable);

        if ($mensaje === null || blank($mensaje['telefono'] ?? null)) {
            return;
        }

        EnviarWhatsApp::dispatch($mensaje['telefono'], $mensaje['plantilla'], $mensaje['parametros'], $mensaje['texto']);
    }
}
