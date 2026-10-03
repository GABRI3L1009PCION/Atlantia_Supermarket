<?php

namespace App\Services\Ml;

use App\Contracts\MlServiceContract;
use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\MlPredictionLog;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente HTTP auditable hacia el microservicio ML FastAPI.
 */
class MlServiceClient implements MlServiceClientInterface, MlServiceContract
{
    /**
     * Ejecuta POST contra ML service.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->request('post', $endpoint, $payload);
    }

    /**
     * Ejecuta GET contra ML service.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, $query);
    }

    /**
     * Detecta fraude en un pedido.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function detectarFraude(array $datos): array
    {
        return $this->post('/fraud/orders', $datos);
    }

    /**
     * Solicita prediccion de demanda.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function predecirDemanda(array $datos): array
    {
        return $this->normalizarPrediccionDemanda($this->post('/predict/demand', $datos));
    }

    /**
     * Solicita recomendaciones de productos.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function recomendar(array $datos): array
    {
        return $this->post('/recommend/products', $datos);
    }

    /**
     * Ejecuta solicitud con auditoria de latencia y errores.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws MlServiceUnavailableException
     */
    private function request(string $method, string $endpoint, array $payload): array
    {
        $inicio = microtime(true);

        try {
            if ($this->usarMock()) {
                return $this->logSuccess($endpoint, $payload, $this->mock($endpoint, $payload), $inicio);
            }

            $response = Http::timeout((int) config('services.ml.timeout_seconds', env('ML_TIMEOUT_SECONDS', 10)))
                ->acceptJson()
                ->withToken((string) config('services.ml.service_token', env('ML_SERVICE_TOKEN')))
                ->{$method}($this->baseUrl().'/'.ltrim($endpoint, '/'), $payload);

            if (! $response->successful()) {
                throw new MlServiceUnavailableException('El microservicio ML no respondio correctamente.');
            }

            return $this->logSuccess($endpoint, $payload, $response->json() ?? [], $inicio);
        } catch (Throwable $exception) {
            MlPredictionLog::query()->create([
                'endpoint' => $endpoint,
                'input' => $payload,
                'output' => null,
                'latencia_ms' => $this->latenciaMs($inicio),
                'estado' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw new MlServiceUnavailableException('El microservicio ML no esta disponible.', previous: $exception);
        }
    }

    /**
     * Registra llamada exitosa.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $output
     * @return array<string, mixed>
     */
    private function logSuccess(string $endpoint, array $payload, array $output, float $inicio): array
    {
        MlPredictionLog::query()->create([
            'endpoint' => $endpoint,
            'input' => $payload,
            'output' => $output,
            'latencia_ms' => $this->latenciaMs($inicio),
            'modelo_version_id' => $output['modelo_version_id'] ?? null,
            'estado' => 'success',
        ]);

        return $output;
    }

    /**
     * URL base del microservicio.
     */
    private function baseUrl(): string
    {
        return rtrim((string) config('services.ml.base_url', env('ML_SERVICE_URL', 'http://localhost:8000/api/v1')), '/');
    }

    /**
     * Determina uso de mock local.
     */
    private function usarMock(): bool
    {
        return (bool) config('services.ml.mock', app()->environment(['local', 'testing']));
    }

    /**
     * Respuestas locales compatibles con endpoints principales.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mock(string $endpoint, array $payload): array
    {
        if (str_contains($endpoint, 'predict/demand') || str_contains($endpoint, 'forecast')) {
            return ['valor_predicho' => 12.0, 'intervalo_inferior' => 8.0, 'intervalo_superior' => 16.0];
        }

        if (str_contains($endpoint, 'recommend')) {
            return ['items' => []];
        }

        return ['accepted' => true, 'endpoint' => $endpoint];
    }

    /**
     * Calcula latencia en milisegundos.
     */
    private function latenciaMs(float $inicio): int
    {
        return (int) round((microtime(true) - $inicio) * 1000);
    }

    /**
     * Agrega la serie diaria de FastAPI al formato historico que persiste Laravel.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizarPrediccionDemanda(array $response): array
    {
        if (! isset($response['puntos']) || ! is_array($response['puntos'])) {
            return $response;
        }

        $totals = [
            'valor_predicho' => 0.0,
            'intervalo_inferior' => 0.0,
            'intervalo_superior' => 0.0,
        ];

        foreach ($response['puntos'] as $point) {
            if (! is_array($point)) {
                continue;
            }

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) ($point[$key] ?? 0);
            }
        }

        return [
            ...$response,
            'valor_predicho' => round($totals['valor_predicho'], 2),
            'intervalo_inferior' => round($totals['intervalo_inferior'], 2),
            'intervalo_superior' => round($totals['intervalo_superior'], 2),
        ];
    }
}
