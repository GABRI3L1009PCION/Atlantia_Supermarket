<?php

namespace App\Services\Ml;

use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Services\Antifraude\DeteccionPatronesService;

/**
 * Servicio ML para deteccion de fraude en pedidos.
 */
class DetectorFraudeService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly DeteccionPatronesService $deteccionPatronesService
    ) {
    }

    /**
     * Evalua fraude con ML y fallback de reglas.
     *
     * @param Pedido $pedido
     * @return FraudAlert|null
     */
    public function evaluar(Pedido $pedido): ?FraudAlert
    {
        try {
            $resultado = $this->mlClient->post('/fraud/order', [
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'total' => (float) $pedido->total,
                'metodo_pago' => $pedido->metodo_pago,
            ]);

            if ((float) ($resultado['score_riesgo'] ?? 0) < 0.65) {
                return null;
            }

            return FraudAlert::query()->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'pedido_id' => $pedido->id,
                'user_id' => $pedido->cliente_id,
                'tipo' => $resultado['tipo'] ?? 'ml_order_fraud',
                'score_riesgo' => $resultado['score_riesgo'],
                'detalle' => $resultado,
                'modelo_version_id' => $resultado['modelo_version_id'] ?? null,
            ]);
        } catch (\Throwable) {
            return $this->deteccionPatronesService->evaluarPedido($pedido);
        }
    }
}
