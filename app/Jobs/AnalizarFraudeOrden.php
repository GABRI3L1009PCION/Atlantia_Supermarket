<?php

namespace App\Jobs;

use App\Enums\EstadoPedido;
use App\Events\FraudeDetectado;
use App\Models\Pedido;
use App\Services\Ml\DetectorFraudeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Analiza un pedido confirmado sin bloquear el checkout del cliente.
 */
class AnalizarFraudeOrden implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Intentos maximos del job.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Tiempo maximo de ejecucion.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Crea el job.
     *
     * @param int $pedidoId
     */
    public function __construct(private readonly int $pedidoId)
    {
        $this->onQueue('ml');
    }

    /**
     * Ejecuta el analisis antifraude ML con fallback local.
     *
     * @param DetectorFraudeService $detectorFraudeService
     * @return void
     */
    public function handle(DetectorFraudeService $detectorFraudeService): void
    {
        $pedido = Pedido::query()
            ->with(['cliente', 'direccion', 'items.producto', 'payments'])
            ->find($this->pedidoId);

        if ($pedido === null || $pedido->estado !== EstadoPedido::Confirmado) {
            return;
        }

        $alerta = $detectorFraudeService->evaluar($pedido);
        $score = $alerta === null ? 0.0 : (float) $alerta->score_riesgo;

        DB::transaction(function () use ($pedido, $alerta, $score): void {
            $pedido->update([
                'fraud_score' => $score,
                'fraud_revisado' => $alerta === null,
                'estado' => $alerta === null ? $pedido->estadoValor() : EstadoPedido::EnRevision->value,
                'notas' => $alerta === null
                    ? $pedido->notas
                    : trim((string) $pedido->notas . "\nPedido movido a revision antifraude automatica."),
            ]);

            if ($alerta !== null) {
                FraudeDetectado::dispatch($pedido->fresh(), $alerta);
            }
        });
    }
}
