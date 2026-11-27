<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Services\Monetizacion\PlanesVendedorService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisos de la prueba gratis de la promoción de apertura (dentro del panel).
 */
class PruebaPlanNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly string $momento,
    ) {}

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
        $plan = PlanesVendedorService::datos((string) ($this->vendor->prueba_plan ?: $this->vendor->plan));
        $conMensualidad = PlanesVendedorService::tieneMensualidad((string) $this->vendor->prueba_plan);
        $base = PlanesVendedorService::datos(PlanesVendedorService::base());
        $fecha = $this->vendor->prueba_hasta?->format('d/m/Y');

        [$titulo, $mensaje] = match ($this->momento) {
            'iniciada' => [
                'Tu prueba gratis empezó',
                $conMensualidad
                    ? "Usas el plan {$plan['nombre']} gratis hasta el {$fecha}."
                    : "Vendes sin pagar la comisión de Atlantia hasta el {$fecha}. La tarifa de la pasarela por pagos con tarjeta sí se cobra.",
            ],
            'por_terminar' => [
                'Tu prueba gratis termina pronto',
                $conMensualidad
                    ? "Tu plan {$plan['nombre']} gratis termina el {$fecha}. Paga tu primera mensualidad para seguir con todos sus beneficios."
                    : "El {$fecha} termina tu periodo sin la comisión de Atlantia. Después se cobra la comisión normal del plan {$base['nombre']}.",
            ],
            default => [
                'Tu prueba gratis terminó',
                $conMensualidad
                    ? "Terminó tu mes gratis del plan {$plan['nombre']}. Si no pagas la mensualidad en los próximos 3 días, tu tienda pasa al plan {$base['nombre']} sin cerrar."
                    : "Terminó tu periodo sin la comisión de Atlantia. Desde hoy se cobra la comisión normal del plan {$base['nombre']}.",
            ],
        };

        return [
            'title' => $titulo,
            'message' => $mensaje,
            'url' => route('vendedor.plan.index', [], false),
            'vendor_uuid' => $this->vendor->uuid,
            'variant' => 'vendor',
        ];
    }
}
