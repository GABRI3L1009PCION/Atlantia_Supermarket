<?php

namespace App\Notifications;

use App\Models\DenunciaTienda;
use App\Services\Vendedores\DenunciaService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa a la administración de una denuncia contra una tienda (y si la tienda se pausó sola).
 */
class DenunciaTiendaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DenunciaTienda $denuncia) {}

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
        $tienda = $this->denuncia->vendor;
        $pausada = $tienda?->pausa_motivo === DenunciaService::MOTIVO_PAUSA;

        return [
            'title' => ($pausada ? 'Tienda pausada por denuncias · ' : 'Denuncia contra ').($tienda?->business_name ?? 'una tienda'),
            'message' => (DenunciaTienda::MOTIVOS[$this->denuncia->motivo] ?? 'Denuncia').'.'
                .($pausada ? ' Acumuló varias denuncias: ya no recibe pedidos y sus pagos están retenidos hasta que la revises.' : ' Revísala en Denuncias.'),
            'url' => route('admin.denuncias.index', [], false),
            'variant' => 'alert',
        ];
    }
}
