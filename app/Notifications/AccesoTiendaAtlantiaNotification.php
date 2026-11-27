<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Services\Vendedores\AccesoVendedorService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enlace para que un empleado cree la contraseña del panel de Atlantia Store.
 */
class AccesoTiendaAtlantiaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly string $enlace,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu acceso al panel de '.$this->vendor->business_name)
            ->greeting('¡Hola!')
            ->line('Te dieron acceso para manejar la tienda '.$this->vendor->business_name.' en Atlantia: pedidos, inventario, horario, reseñas y cupones.')
            ->line('Crea tu contraseña con este botón. El enlace vence en '.AccesoVendedorService::HORAS_VIGENCIA.' horas.')
            ->action('Crear mi contraseña', $this->enlace)
            ->line('Si no esperabas este correo, ignóralo.');
    }
}
