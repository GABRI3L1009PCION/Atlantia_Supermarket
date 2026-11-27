<?php

namespace App\Jobs;

use App\Services\Monetizacion\NovedadesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Aviso de novedades y ofertas en los dias y la hora configurados (una vez por dia).
 */
class EnviarNovedadesYOfertas implements ShouldQueue
{
    use Queueable;

    public function handle(NovedadesService $novedades): void
    {
        $ahora = now();
        $dias = (array) config('monetizacion.novedades.dias', [2, 5]);

        if (! in_array($ahora->dayOfWeek, $dias, true) || $ahora->format('H:i') < (string) config('monetizacion.novedades.hora', '10:00')) {
            return;
        }

        /* Cache::add solo guarda si no existe: evita enviar dos veces el mismo dia. */
        if (! Cache::add('novedades-enviadas:'.$ahora->toDateString(), true, $ahora->copy()->endOfDay())) {
            return;
        }

        $novedades->enviarAviso();
    }
}
