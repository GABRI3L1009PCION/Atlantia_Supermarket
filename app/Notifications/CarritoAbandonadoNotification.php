<?php

namespace App\Notifications;

use App\Models\Carrito;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio amable de productos que quedaron en el carrito.
 */
class CarritoAbandonadoNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Carrito $carrito, private readonly int $productos) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $canales = ['database'];

        if (! str_ends_with((string) $notifiable->email, '.atlantia.local')) {
            $canales[] = 'mail';
        }

        if (WebPushChannel::disponiblePara($notifiable)) {
            $canales[] = WebPushChannel::class;
        }

        if (app(WhatsAppService::class)->habilitado() && WhatsAppService::normalizarTelefono($notifiable->phone) !== null) {
            $canales[] = WhatsAppChannel::class;
        }

        return $canales;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Tu carrito te espera',
            'message' => $this->texto($notifiable),
            'route' => route('cliente.carrito.index'),
            'variant' => 'info',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dejaste productos en tu carrito')
            ->greeting('Hola '.strtok((string) $notifiable->name, ' '))
            ->line($this->texto($notifiable))
            ->action('Terminar mi compra', route('cliente.carrito.index'));
    }

    /**
     * @return array{titulo: string, cuerpo: string, url: string}
     */
    public function toWebPush(object $notifiable): array
    {
        $datos = $this->toDatabase($notifiable);

        return ['titulo' => (string) $datos['title'], 'cuerpo' => (string) $datos['message'], 'url' => (string) $datos['route']];
    }

    /**
     * @return array{telefono: ?string, plantilla: string, parametros: array<int, string>, texto: string}
     */
    public function toWhatsApp(object $notifiable): array
    {
        $nombre = (string) strtok((string) $notifiable->name, ' ');

        return [
            'telefono' => WhatsAppService::normalizarTelefono($notifiable->phone),
            'plantilla' => 'carrito_abandonado',
            'parametros' => [$nombre, (string) $this->productos, route('cliente.carrito.index')],
            'texto' => $this->texto($notifiable).' '.route('cliente.carrito.index'),
        ];
    }

    private function texto(object $notifiable): string
    {
        $productos = $this->productos === 1 ? '1 producto' : "{$this->productos} productos";

        return 'Hola '.strtok((string) $notifiable->name, ' ').", dejaste {$productos} en tu carrito de Atlantia Store. Te los guardamos para que termines tu compra cuando quieras.";
    }
}
