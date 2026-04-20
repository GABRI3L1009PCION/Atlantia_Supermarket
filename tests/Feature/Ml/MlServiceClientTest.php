<?php

namespace Tests\Feature\Ml;

use App\Models\Ml\MlPredictionLog;
use App\Services\Ml\MlServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function testMockedMlRequestIsLogged(): void
    {
        config(['services.ml.mock' => true]);

        $response = app(MlServiceClient::class)->post('forecast/demand', [
            'producto_id' => 10,
            'vendor_id' => 2,
            'horizonte_dias' => 7,
        ]);

        $this->assertSame(12.0, $response['valor_predicho']);
        $this->assertDatabaseHas('ml_prediction_logs', [
            'endpoint' => 'forecast/demand',
            'estado' => 'success',
        ]);
        $this->assertSame(1, MlPredictionLog::query()->count());
    }
}
