<?php

namespace App\Jobs\Ml;

use App\Events\PrediccionGenerada;
use App\Models\Producto;
use App\Services\Ml\PrediccionDemandaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Solicita prediccion de demanda para un producto.
 */
class SolicitarPrediccionDemanda implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'ml';

    public int $tries = 3;

    /**
     * Crea el job.
     *
     * @param int $productoId
     * @param int $horizonteDias
     */
    public function __construct(private readonly int $productoId, private readonly int $horizonteDias = 14)
    {
    }

    /**
     * Genera la prediccion y emite evento.
     *
     * @param PrediccionDemandaService $prediccionDemandaService
     * @return void
     */
    public function handle(PrediccionDemandaService $prediccionDemandaService): void
    {
        $producto = Producto::query()->findOrFail($this->productoId);
        $prediction = $prediccionDemandaService->generar($producto, $this->horizonteDias);

        PrediccionGenerada::dispatch($prediction);
    }
}
