<?php

namespace App\Jobs;

use App\Models\Carrito;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Elimina carritos abandonados con mas de siete dias sin actividad.
 */
class LimpiarCarritosAbandonados implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Ejecuta la limpieza operativa.
     */
    public function handle(): void
    {
        Carrito::query()
            ->with('items')
            ->where('estado', 'activo')
            ->where(function ($query): void {
                $query->where('updated_at', '<=', now()->subDays(7))
                    ->orWhere('expira_at', '<=', now());
            })
            ->chunkById(100, function ($carritos): void {
                foreach ($carritos as $carrito) {
                    $carrito->items()->delete();
                    $carrito->delete();
                }
            });
    }
}
