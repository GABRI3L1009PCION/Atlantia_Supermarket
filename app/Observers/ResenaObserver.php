<?php

namespace App\Observers;

use App\Jobs\Ml\AnalizarResenaConMl;
use App\Models\Resena;
use Illuminate\Support\Str;

/**
 * Observer de resenas para UUID y analisis ML.
 */
class ResenaObserver
{
    /**
     * Asigna UUID seguro antes de crear.
     *
     * @param Resena $resena
     * @return void
     */
    public function creating(Resena $resena): void
    {
        if (empty($resena->uuid)) {
            $resena->uuid = (string) Str::uuid();
        }
    }

    /**
     * Dispara analisis ML despues de crear una resena.
     *
     * @param Resena $resena
     * @return void
     */
    public function created(Resena $resena): void
    {
        AnalizarResenaConMl::dispatch($resena->id);
    }
}
