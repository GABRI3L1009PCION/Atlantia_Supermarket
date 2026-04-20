<?php

namespace App\Jobs\Ml;

use App\Events\ResenaMarcadaSospechosa;
use App\Models\Resena;
use App\Services\Ml\DetectorResenaFalsaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Analiza una resena con el detector ML de fraude/NLP.
 */
class AnalizarResenaConMl implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'ml';

    /**
     * Crea el job.
     *
     * @param int $resenaId
     */
    public function __construct(private readonly int $resenaId)
    {
    }

    /**
     * Ejecuta el analisis ML.
     *
     * @param DetectorResenaFalsaService $detector
     * @return void
     */
    public function handle(DetectorResenaFalsaService $detector): void
    {
        $resena = Resena::query()->findOrFail($this->resenaId);
        $flag = $detector->evaluar($resena);

        if ($flag !== null) {
            ResenaMarcadaSospechosa::dispatch($resena->refresh(), $flag);
        }
    }
}
