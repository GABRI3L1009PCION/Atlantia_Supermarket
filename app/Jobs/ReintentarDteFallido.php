<?php

namespace App\Jobs;

use App\Models\Dte\DteFactura;
use App\Services\Fel\DteContingenciaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reintenta certificacion de un DTE fallido o pendiente.
 */
class ReintentarDteFallido implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'fel';

    public int $tries = 5;

    /**
     * @var array<int, int>
     */
    public array $backoff = [300, 900, 1800, 3600, 7200];

    /**
     * Crea el job.
     *
     * @param int $dteId
     */
    public function __construct(private readonly int $dteId)
    {
    }

    /**
     * Ejecuta el reintento.
     *
     * @param DteContingenciaService $contingenciaService
     * @return void
     */
    public function handle(DteContingenciaService $contingenciaService): void
    {
        $dte = DteFactura::query()->findOrFail($this->dteId);
        $contingenciaService->reintentar($dte);
    }
}
