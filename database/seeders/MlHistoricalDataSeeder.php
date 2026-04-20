<?php

namespace Database\Seeders;

use App\Models\Ml\FraudAlert;
use App\Models\Ml\MlMetric;
use App\Models\Ml\MlModelVersion;
use App\Models\Ml\MlPredictionLog;
use App\Models\Ml\MlTrainingJob;
use App\Models\Ml\ProductRecommendation;
use App\Models\Ml\RestockSuggestion;
use App\Models\Ml\ReviewFlag;
use App\Models\Ml\SalesPrediction;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder de datos historicos y operativos para ML.
 */
class MlHistoricalDataSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de datos ML.
     */
    public function run(): void
    {
        $demandModel = $this->modelVersion(
            'demand_forecast_prophet',
            '2026.04.18',
            'mlflow://atlantia/demand_forecast_prophet/2026.04.18'
        );
        $recommendationModel = $this->modelVersion(
            'product_recommendation_hybrid',
            '2026.04.18',
            'mlflow://atlantia/product_recommendation_hybrid/2026.04.18'
        );
        $fraudModel = $this->modelVersion(
            'fraud_review_xgboost',
            '2026.04.18',
            'mlflow://atlantia/fraud_review_xgboost/2026.04.18'
        );

        $this->trainingJobs($demandModel, $recommendationModel, $fraudModel);
        $this->salesPredictions($demandModel);
        $this->restockSuggestions($demandModel);
        $this->productRecommendations($recommendationModel);
        $this->metrics($demandModel, $recommendationModel, $fraudModel);
        $this->predictionLogs($demandModel, $recommendationModel, $fraudModel);
        $this->fraudAlerts($fraudModel);
        $this->reviewFlags($fraudModel);
    }

    /**
     * Crea o actualiza una version de modelo.
     *
     * @param string $name
     * @param string $version
     * @param string $artifactPath
     * @return MlModelVersion
     */
    private function modelVersion(string $name, string $version, string $artifactPath): MlModelVersion
    {
        $modelVersion = MlModelVersion::query()->firstOrNew([
            'nombre_modelo' => $name,
            'version' => $version,
        ]);

        $modelVersion->fill([
            'uuid' => $modelVersion->uuid ?? (string) Str::uuid(),
            'ruta_artefacto' => $artifactPath,
            'metricas' => match ($name) {
                'demand_forecast_prophet' => ['mape' => 8.73, 'rmse' => 4.12],
                'product_recommendation_hybrid' => ['precision_at_10' => 0.31, 'coverage' => 0.82],
                default => ['auc' => 0.91, 'precision' => 0.87],
            },
            'fecha_entrenamiento' => now()->subDays(5),
            'fecha_deploy' => now()->subDays(3),
            'estado' => 'production',
            'entrenado_por' => User::query()->where('email', 'admin@atlantia.test')->value('id'),
        ]);
        $modelVersion->save();

        return $modelVersion;
    }

    /**
     * Crea jobs de entrenamiento historicos.
     *
     * @param MlModelVersion $demandModel
     * @param MlModelVersion $recommendationModel
     * @param MlModelVersion $fraudModel
     */
    private function trainingJobs(
        MlModelVersion $demandModel,
        MlModelVersion $recommendationModel,
        MlModelVersion $fraudModel
    ): void {
        foreach ([$demandModel, $recommendationModel, $fraudModel] as $model) {
            MlTrainingJob::query()->updateOrCreate(
                ['uuid' => '00000000-0000-4000-8000-' . str_pad((string) $model->id, 12, '0', STR_PAD_LEFT)],
                [
                    'modelo_nombre' => $model->nombre_modelo,
                    'modelo_version_id' => $model->id,
                    'inicio_at' => now()->subDays(5)->subMinutes(45),
                    'fin_at' => now()->subDays(5),
                    'estado' => 'completed',
                    'metricas_finales' => $model->metricas,
                    'dataset_size' => match ($model->nombre_modelo) {
                        'demand_forecast_prophet' => 1260,
                        'product_recommendation_hybrid' => 840,
                        default => 430,
                    },
                    'error_log' => null,
                ]
            );
        }
    }

    /**
     * Crea predicciones de demanda por producto.
     *
     * @param MlModelVersion $model
     */
    private function salesPredictions(MlModelVersion $model): void
    {
        foreach (Producto::query()->with('vendor')->get() as $producto) {
            foreach ([7, 14, 30] as $horizon) {
                $base = match (true) {
                    str_contains(strtolower($producto->nombre), 'camaron') => 18,
                    str_contains(strtolower($producto->nombre), 'banano') => 55,
                    str_contains(strtolower($producto->nombre), 'frijol') => 42,
                    default => 25,
                };
                $predicho = $base + ($horizon / 7 * 3);

                SalesPrediction::query()->updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'fecha_prediccion' => now()->addDays($horizon)->toDateString(),
                        'horizonte_dias' => $horizon,
                        'modelo_version_id' => $model->id,
                    ],
                    [
                        'vendor_id' => $producto->vendor_id,
                        'valor_predicho' => $predicho,
                        'valor_real' => $horizon === 7 ? max(0, $predicho - 2) : null,
                        'intervalo_inferior' => max(0, $predicho - 5),
                        'intervalo_superior' => $predicho + 7,
                    ]
                );
            }
        }
    }

    /**
     * Crea sugerencias de reabastecimiento.
     *
     * @param MlModelVersion $model
     */
    private function restockSuggestions(MlModelVersion $model): void
    {
        foreach (Producto::query()->with('inventario')->get() as $producto) {
            $stock = (int) ($producto->inventario?->stock_actual ?? 0);
            $urgencia = match (true) {
                $stock <= 10 => 'critica',
                $stock <= 25 => 'alta',
                $stock <= 50 => 'media',
                default => 'baja',
            };

            RestockSuggestion::query()->updateOrCreate(
                ['producto_id' => $producto->id, 'modelo_version_id' => $model->id],
                [
                    'vendor_id' => $producto->vendor_id,
                    'stock_actual' => $stock,
                    'stock_sugerido' => max(60, $stock + 35),
                    'dias_hasta_quiebre' => match ($urgencia) {
                        'critica' => 2,
                        'alta' => 5,
                        'media' => 10,
                        default => 18,
                    },
                    'urgencia' => $urgencia,
                    'aceptada' => false,
                    'aceptada_at' => null,
                ]
            );
        }
    }

    /**
     * Crea recomendaciones para el cliente demo.
     *
     * @param MlModelVersion $model
     */
    private function productRecommendations(MlModelVersion $model): void
    {
        $clienteId = User::query()->where('email', 'cliente@atlantia.test')->value('id');

        if ($clienteId === null) {
            return;
        }

        foreach (Producto::query()->orderBy('nombre')->limit(6)->get()->values() as $position => $producto) {
            ProductRecommendation::query()->updateOrCreate(
                [
                    'cliente_id' => $clienteId,
                    'producto_id' => $producto->id,
                    'algoritmo' => 'hybrid_content_collaborative',
                ],
                [
                    'score' => round(0.95 - ($position * 0.07), 6),
                    'posicion' => $position + 1,
                    'modelo_version_id' => $model->id,
                ]
            );
        }
    }

    /**
     * Crea metricas diarias por modelo.
     *
     * @param MlModelVersion $demandModel
     * @param MlModelVersion $recommendationModel
     * @param MlModelVersion $fraudModel
     */
    private function metrics(
        MlModelVersion $demandModel,
        MlModelVersion $recommendationModel,
        MlModelVersion $fraudModel
    ): void {
        $metrics = [
            [$demandModel, 8.73, 4.1200, 0.8400, 0.0700],
            [$recommendationModel, null, null, 0.7800, 0.0500],
            [$fraudModel, null, null, 0.9100, 0.1100],
        ];

        foreach ($metrics as [$model, $mape, $rmse, $r2, $drift]) {
            MlMetric::query()->updateOrCreate(
                ['modelo_version_id' => $model->id, 'fecha' => now()->toDateString()],
                [
                    'mape' => $mape,
                    'rmse' => $rmse,
                    'r2' => $r2,
                    'drift_score' => $drift,
                ]
            );
        }
    }

    /**
     * Crea logs de llamadas ML.
     *
     * @param MlModelVersion $demandModel
     * @param MlModelVersion $recommendationModel
     * @param MlModelVersion $fraudModel
     */
    private function predictionLogs(
        MlModelVersion $demandModel,
        MlModelVersion $recommendationModel,
        MlModelVersion $fraudModel
    ): void {
        $logs = [
            ['/predict/demand', $demandModel, ['producto_id' => 1, 'horizon' => 7], ['predicted_units' => 45]],
            ['/recommend/products', $recommendationModel, ['cliente_id' => 1], ['productos' => [1, 2, 3]]],
            ['/fraud/review', $fraudModel, ['pedido_id' => 1], ['risk_score' => 0.18]],
        ];

        foreach ($logs as [$endpoint, $model, $input, $output]) {
            MlPredictionLog::query()->updateOrCreate(
                ['endpoint' => $endpoint, 'modelo_version_id' => $model->id],
                [
                    'input' => $input,
                    'output' => $output,
                    'latencia_ms' => match ($endpoint) {
                        '/recommend/products' => 95,
                        '/fraud/review' => 58,
                        default => 124,
                    },
                    'estado' => 'success',
                    'error' => null,
                ]
            );
        }
    }

    /**
     * Crea alertas antifraude de baja y media prioridad.
     *
     * @param MlModelVersion $model
     */
    private function fraudAlerts(MlModelVersion $model): void
    {
        $pedido = Pedido::query()->where('numero_pedido', 'ATL-20260418-0001')->first();
        $clienteId = User::query()->where('email', 'cliente@atlantia.test')->value('id');

        FraudAlert::query()->updateOrCreate(
            ['uuid' => '00000000-0000-4000-8000-000000000301'],
            [
                'pedido_id' => $pedido?->id,
                'user_id' => $clienteId,
                'tipo' => 'pedido_monto_inusual',
                'score_riesgo' => 0.280000,
                'detalle' => [
                    'motivo' => 'Monto ligeramente superior al promedio del cliente nuevo.',
                    'accion_sugerida' => 'Monitoreo sin bloqueo.',
                ],
                'revisada' => true,
                'resuelta' => true,
                'revisada_por' => User::query()->where('email', 'empleado@atlantia.test')->value('id'),
                'revisada_at' => now()->subDay(),
                'modelo_version_id' => $model->id,
            ]
        );
    }

    /**
     * Crea flags ML para resenas.
     *
     * @param MlModelVersion $model
     */
    private function reviewFlags(MlModelVersion $model): void
    {
        $resena = Resena::query()->first();

        if ($resena === null) {
            return;
        }

        ReviewFlag::query()->updateOrCreate(
            ['resena_id' => $resena->id, 'razon_ml' => 'lenguaje_promocional_bajo'],
            [
                'score_sospecha' => 0.180000,
                'revisada' => true,
                'accion_tomada' => 'aprobada',
                'revisada_por' => User::query()->where('email', 'empleado@atlantia.test')->value('id'),
                'revisada_at' => now()->subDay(),
                'modelo_version_id' => $model->id,
            ]
        );
    }
}
