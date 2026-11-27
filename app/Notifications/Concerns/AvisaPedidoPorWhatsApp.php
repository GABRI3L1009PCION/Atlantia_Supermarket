<?php

namespace App\Notifications\Concerns;

use App\Models\Pedido;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Support\Facades\URL;

/**
 * Ayudas comunes para avisar el estado del pedido por WhatsApp.
 */
trait AvisaPedidoPorWhatsApp
{
    /**
     * Canales: base de datos y WhatsApp cuando hay telefono y canal activo.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $canales = ['database'];

        /* Canal principal (gratis): notificaciones push del navegador o de la app instalada. */
        if (WebPushChannel::disponiblePara($notifiable)) {
            $canales[] = WebPushChannel::class;
        }

        /* WhatsApp es opcional y tiene costo: solo si se activa en la configuracion. */
        if (app(WhatsAppService::class)->habilitado() && $this->telefonoWhatsApp($notifiable) !== null) {
            $canales[] = WhatsAppChannel::class;
        }

        return $canales;
    }

    /**
     * Notificacion push con el mismo texto del aviso de la plataforma.
     *
     * @return array{titulo: string, cuerpo: string, url: string, etiqueta: string}
     */
    public function toWebPush(object $notifiable): array
    {
        $datos = $this->toDatabase($notifiable);
        $esInvitado = str_ends_with((string) ($notifiable->email ?? ''), '@invitados.atlantia.local');

        return [
            'titulo' => (string) $datos['title'],
            'cuerpo' => (string) $datos['message'],
            'url' => $esInvitado ? $this->enlaceSeguimiento($notifiable) : (string) $datos['route'],
            'etiqueta' => 'pedido-'.$this->pedidoAvisado()->uuid,
        ];
    }

    /**
     * Arma el mensaje con el texto y los parametros de la plantilla.
     *
     * @param  array<int, string>  $parametros
     * @return array{telefono: ?string, plantilla: string, parametros: array<int, string>, texto: string}
     */
    protected function mensajeWhatsApp(object $notifiable, string $plantilla, array $parametros, string $texto): array
    {
        return [
            'telefono' => $this->telefonoWhatsApp($notifiable),
            'plantilla' => $plantilla,
            'parametros' => array_map('strval', $parametros),
            'texto' => $texto,
        ];
    }

    protected function telefonoWhatsApp(object $notifiable): ?string
    {
        $pedido = $this->pedidoAvisado();
        $pedido->loadMissing('direccion');

        return WhatsAppService::normalizarTelefono($pedido->direccion?->telefono_contacto)
            ?? WhatsAppService::normalizarTelefono($notifiable->phone ?? null);
    }

    protected function nombreCliente(object $notifiable): string
    {
        $pedido = $this->pedidoAvisado();
        $nombre = trim((string) ($pedido->direccion?->nombre_contacto ?? $notifiable->name ?? ''));

        return $nombre === '' ? 'cliente' : (string) strtok($nombre, ' ');
    }

    /**
     * Enlace de seguimiento. Los invitados reciben un enlace firmado que no requiere cuenta.
     */
    protected function enlaceSeguimiento(object $notifiable): string
    {
        $pedido = $this->pedidoAvisado();

        if (str_ends_with((string) ($notifiable->email ?? ''), '@invitados.atlantia.local')) {
            return URL::temporarySignedRoute('cliente.pedidos.guest-seguimiento', now()->addDays(3), $pedido);
        }

        return route('cliente.pedidos.seguimiento', $pedido);
    }

    abstract protected function pedidoAvisado(): Pedido;
}
