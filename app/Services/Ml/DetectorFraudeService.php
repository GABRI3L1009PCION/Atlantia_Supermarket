<?php

namespace App\Services\Ml;

use App\Contracts\MlServiceContract;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Services\Antifraude\DeteccionPatronesService;
use Illuminate\Support\Str;

/**
 * Servicio ML para deteccion de fraude en pedidos.
 */
class DetectorFraudeService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceContract $mlClient,
        private readonly DeteccionPatronesService $deteccionPatronesService
    ) {}

    /**
     * Evalua fraude con ML y fallback de reglas.
     */
    public function evaluar(Pedido $pedido): ?FraudAlert
    {
        try {
            $pedido->loadMissing(['items', 'payments']);

            $resultado = $this->mlClient->detectarFraude([
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'total' => (float) $pedido->total,
                'metodo_pago' => $pedido->metodoPagoValor(),
                'intentos_pago' => max(1, $pedido->payments->count()),
                'items' => $pedido->items->map(fn ($item): array => [
                    'producto_id' => (int) $item->producto_id,
                    'cantidad' => (int) $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario_snapshot,
                ])->values()->all(),
            ]);

            if ((float) ($resultado['score_riesgo'] ?? 0) < 0.65) {
                return null;
            }

            return FraudAlert::query()->create([
                'uuid' => (string) Str::uuid(),
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
