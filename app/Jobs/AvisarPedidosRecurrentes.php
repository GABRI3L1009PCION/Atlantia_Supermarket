<?php

namespace App\Jobs;

use App\Models\PedidoRecurrente;
use App\Notifications\PedidoRecurrenteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * El dia elegido avisa al cliente que su pedido semanal esta listo para confirmar.
 * No cobra ni crea el pedido solo: el cliente revisa y confirma (puede cambiar productos).
 */
class AvisarPedidosRecurrentes implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $ahora = now();

        PedidoRecurrente::query()
            ->with(['user', 'pedidoOrigen'])
            ->where('activo', true)
            ->where('dia_semana', $ahora->dayOfWeek)
            ->where('hora_aviso', '<=', $ahora->format('H:i:s'))
            ->where(fn ($q) => $q->whereNull('ultimo_aviso_at')->orWhere('ultimo_aviso_at', '<', $ahora->copy()->startOfDay()))
            ->chunkById(100, function ($recurrentes): void {
                foreach ($recurrentes as $recurrente) {
                    $recurrente->update(['ultimo_aviso_at' => now()]);

                    if ($recurrente->user?->status === 'active' && $recurrente->pedidoOrigen !== null) {
                        $recurrente->user->notify(new PedidoRecurrenteNotification($recurrente));
                    }
                }
            });
    }
}
