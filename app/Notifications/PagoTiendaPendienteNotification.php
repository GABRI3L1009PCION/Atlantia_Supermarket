<?php

namespace App\Notifications;

use App\Models\LiquidacionVendedor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa a la administración que hay que transferirle a una tienda (pago programado según su frecuencia).
 */
class PagoTiendaPendienteNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LiquidacionVendedor $liquidacion) {}

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
        $tienda = $this->liquidacion->vendor?->business_name ?? 'una tienda';
        $plazo = $this->liquidacion->pagar_antes_de;

        return [
            'title' => ($this->liquidacion->vendor?->payment_frequency === 'instantanea' ? 'Pago al instante · ' : 'Pago programado · ').$tienda,
            'message' => 'Transfiere Q'.number_format((float) $this->liquidacion->monto_neto, 2).' a '.$tienda
                .($plazo ? ' antes de las '.$plazo->format('h:i a').($plazo->isToday() ? '' : ' del '.$plazo->format('d/m')) : '').'.',
            'url' => route('admin.liquidaciones.show', $this->liquidacion, false),
            'variant' => 'payment',
        ];
    }
}
