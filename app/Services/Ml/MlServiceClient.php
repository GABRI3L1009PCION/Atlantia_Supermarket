<?php

namespace App\Services\Ml;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\MlPredictionLog;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente HTTP auditable hacia el microservicio ML FastAPI.
 */
class MlServiceClient implements MlServiceClientInterface
{
    /**
     * Ejecuta POST contra ML service.
     *
     * @param string $endpoint
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->request('post', $endpoint, $payload);
    }

    /**
     * Ejecuta GET contra ML service.
     *
     * @param string $endpoint
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, $query);
    }

    /**
     * Ejecuta solicitud con auditoria de latencia y errores.
     *
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $payload
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

            $response = Http::timeout((int) config('services.ml.timeout', env('ML_TIMEOUT_SECONDS', 10)))
                ->acceptJson()
                ->withToken((string) config('services.ml.token', env('ML_SERVICE_TOKEN')))
                ->{$method}($this->baseUrl() . '/' . ltrim($endpoint, '/'), $payload);

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
     * @param string $endpoint
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $output
     * @param float $inicio
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
     *
     * @return string
     */
    private function baseUrl(): string
    {
        return rtrim((string) config('services.ml.base_url', env('ML_SERVICE_URL', 'http://localhost:8000')), '/');
    }

    /**
     * Determina uso de mock local.
     *
     * @return bool
     */
    private function usarMock(): bool
    {
        return (bool) config('services.ml.mock', app()->environment(['local', 'testing']));
    }

    /**
     * Respuestas locales compatibles con endpoints principales.
     *
     * @param string $endpoint
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function mock(string $endpoint, array $payload): array
    {
        if (str_contains($endpoint, 'forecast')) {
            return ['valor_predicho' => 12.0, 'intervalo_inferior' => 8.0, 'intervalo_superior' => 16.0];
        }

        if (str_contains($endpoint, 'recommend')) {
            return ['items' => []];
        }

        return ['accepted' => true, 'endpoint' => $endpoint];
    }

    /**
     * Calcula latencia en milisegundos.
     *
     * @param float $inicio
     * @return int
     */
    private function latenciaMs(float $inicio): int
    {
        return (int) round((microtime(true) - $inicio) * 1000);
    }
}
