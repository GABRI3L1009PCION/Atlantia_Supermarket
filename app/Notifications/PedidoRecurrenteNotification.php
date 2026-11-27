<?php

namespace App\Notifications;

use App\Models\PedidoRecurrente;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso semanal: "tu pedido de cada sabado esta listo, confirmalo con un toque".
 */
class PedidoRecurrenteNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly PedidoRecurrente $recurrente) {}

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
            'title' => 'Tu pedido semanal',
            'message' => $this->texto(),
            'route' => $this->enlace(),
            'variant' => 'info',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu pedido semanal de Atlantia Store')
            ->line($this->texto())
            ->action('Cargar mi pedido', $this->enlace());
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
        return [
            'telefono' => WhatsAppService::normalizarTelefono($notifiable->phone),
            'plantilla' => 'pedido_recurrente',
            'parametros' => [(string) strtok((string) $notifiable->name, ' '), $this->enlace()],
            'texto' => $this->texto().' '.$this->enlace(),
        ];
    }

    private function texto(): string
    {
        return 'Hoy es '.PedidoRecurrente::DIAS[$this->recurrente->dia_semana].': tu pedido de siempre está listo. Ábrelo, revisa y confírmalo con un toque.';
    }

    private function enlace(): string
    {
        return route('cliente.recurrentes.cargar', $this->recurrente);
    }
}
