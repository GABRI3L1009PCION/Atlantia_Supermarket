<?php

namespace App\Jobs\Ml;

use App\Models\Pedido;
use App\Services\Ml\DetectorFraudeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evalua riesgo antifraude de un pedido.
 */
class DetectarFraudeEnPedido implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    /**
     * Crea el job.
     *
     * @param int $pedidoId
     */
    public function __construct(private readonly int $pedidoId)
    {
    }

    /**
     * Ejecuta analisis antifraude.
     *
     * @param DetectorFraudeService $detectorFraudeService
     * @return void
     */
    public function handle(DetectorFraudeService $detectorFraudeService): void
    {
        $pedido = Pedido::query()->with(['cliente', 'items.producto'])->findOrFail($this->pedidoId);

        $detectorFraudeService->evaluar($pedido);
    }
}
