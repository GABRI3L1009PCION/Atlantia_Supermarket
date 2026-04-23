<?php

namespace App\Listeners;

use App\Events\DevolucionAprobada;
use App\Models\SentEmail;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

/**
 * Registra notificacion y email para devoluciones aprobadas.
 */
class EnviarEmailDevolucionAprobada implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa la notificacion de devolucion aprobada.
     */
    public function handle(DevolucionAprobada $event): void
    {
        $devolucion = $event->devolucion->loadMissing(['pedido', 'user']);
        $user = $devolucion->user;

        if ($user === null) {
            return;
        }

        app(NotificationService::class)->create($user, 'devolucion.aprobada', [
            'titulo' => 'Devolucion aprobada',
            'mensaje' => "Aprobamos la devolucion del pedido {$devolucion->pedido?->numero_pedido}.",
            'pedido_uuid' => $devolucion->pedido?->uuid,
            'monto_reembolso' => $devolucion->monto_reembolso,
        ]);

        SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => 'Tu devolucion fue aprobada',
            'template' => 'devolucion.aprobada',
            'status' => 'queued',
            'metadata' => [
                'pedido' => $devolucion->pedido?->numero_pedido,
                'monto_reembolso' => $devolucion->monto_reembolso,
            ],
        ]);
    }
}
