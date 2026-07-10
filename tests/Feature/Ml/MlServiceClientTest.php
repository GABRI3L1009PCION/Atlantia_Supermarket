<?php

namespace Tests\Feature\Ml;

use App\Models\Ml\MlPredictionLog;
use App\Services\Ml\MlServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pruebas del cliente Laravel hacia el microservicio ML.
 */
class MlServiceClientTest extends TestCase
{
    use RefreshDatabase;

    /**
     * En testing usa mock local y deja auditoria de la llamada.
     */
    public function test_mocked_ml_request_is_logged(): void
    {
        config(['services.ml.mock' => true]);

        $response = app(MlServiceClient::class)->post('/predict/demand', [
            'producto_id' => 10,
            'vendor_id' => 2,
            'horizonte_dias' => 7,
        ]);

        $this->assertSame(12.0, $response['valor_predicho']);
        $this->assertDatabaseHas('ml_prediction_logs', [
            'endpoint' => '/predict/demand',
            'estado' => 'success',
        ]);
        $this->assertSame(1, MlPredictionLog::query()->count());
    }

    /**
     * Normaliza la respuesta diaria de FastAPI al formato agregado usado por Laravel.
     */
    public function test_demand_prediction_points_are_aggregated(): void
    {
        config([
            'services.ml.mock' => false,
            'services.ml.base_url' => 'http://ml.test/api/v1',
            'services.ml.service_token' => 'ml-token',
        ]);

        Http::fake([
            'http://ml.test/api/v1/predict/demand' => Http::response([
                'producto_id' => 10,
                'vendor_id' => 2,
                'horizonte_dias' => 2,
                'modelo' => 'deterministic',
                'puntos' => [
                    ['fecha' => '2026-06-19', 'valor_predicho' => 3.5, 'intervalo_inferior' => 2.0, 'intervalo_superior' => 4.0],
                    ['fecha' => '2026-06-20', 'valor_predicho' => 4.5, 'intervalo_inferior' => 3.0, 'intervalo_superior' => 6.0],
                ],
            ]),
        ]);

        $response = app(MlServiceClient::class)->predecirDemanda([
            'producto_id' => 10,
            'vendor_id' => 2,
            'horizonte_dias' => 2,
        ]);

        $this->assertSame(8.0, $response['valor_predicho']);
        $this->assertSame(5.0, $response['intervalo_inferior']);
        $this->assertSame(10.0, $response['intervalo_superior']);
    }
}
