<?php

namespace App\Listeners;

use App\Jobs\Ml\AnalizarResenaConMl;
use App\Models\Resena;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Dispara analisis ML despues de crear una resena.
 */
class DispararAnalisisMlTrasResena implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa el modelo resena recibido desde observer.
     *
     * @param Resena $resena
     * @return void
     */
    public function handle(Resena $resena): void
    {
        AnalizarResenaConMl::dispatch($resena->id);
    }
}
