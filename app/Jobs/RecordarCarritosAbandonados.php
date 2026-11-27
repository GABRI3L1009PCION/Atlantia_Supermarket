<?php

namespace App\Jobs;

use App\Models\Carrito;
use App\Notifications\CarritoAbandonadoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envia un solo recordatorio por carrito, unas horas despues de la ultima actividad.
 * Solo a clientes con cuenta (los carritos de visitantes no tienen a quien avisar).
 */
class RecordarCarritosAbandonados implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $desde = now()->subHours((int) config('atlantia.carrito_abandonado.horas_max', 48));
        $hasta = now()->subHours((int) config('atlantia.carrito_abandonado.horas', 3));

        Carrito::query()
            ->with('user')
            ->withCount('items')
            ->where('estado', 'activo')
            ->whereNotNull('user_id')
            ->whereNull('recordatorio_enviado_at')
            ->whereBetween('updated_at', [$desde, $hasta])
            ->whereHas('items')
            ->chunkById(100, function ($carritos): void {
                foreach ($carritos as $carrito) {
                    $carrito->forceFill(['recordatorio_enviado_at' => now()])->saveQuietly();

                    if ($carrito->user !== null && $carrito->user->status === 'active') {
                        $carrito->user->notify(new CarritoAbandonadoNotification($carrito, (int) $carrito->items_count));
                    }
                }
            });
    }
}
