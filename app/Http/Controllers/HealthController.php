<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Endpoint de salud operativa para monitoreo externo.
 */
class HealthController extends Controller
{
    /**
     * Devuelve el estado de dependencias criticas.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'meilisearch' => $this->checkMeilisearch(),
            'ml_service' => $this->checkMlService(),
        ];

        $failed = collect($checks)->first(fn (string $status): bool => $status !== 'ok');
        $status = $failed === false ? 'ok' : 'degraded';
        $code = $failed === false ? 200 : 503;

        return response()->json([
            'status' => $status,
            'database' => $checks['database'],
            'redis' => $checks['redis'],
            'meilisearch' => $checks['meilisearch'],
            'ml_service' => $checks['ml_service'],
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * Verifica la conexion a MySQL.
     *
     * @return string
     */
    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable $exception) {
            Log::warning('Health check database failed', [
                'service' => 'database',
                'error' => $exception->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Verifica la conexion a Redis.
     *
     * @return string
     */
    private function checkRedis(): string
    {
        try {
            $response = Redis::connection('cache')->ping();

            return in_array($response, [true, '+PONG', 'PONG'], true) ? 'ok' : 'error';
        } catch (Throwable $exception) {
            Log::warning('Health check redis failed', [
                'service' => 'redis',
                'error' => $exception->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Verifica disponibilidad de Meilisearch.
     *
     * @return string
     */
    private function checkMeilisearch(): string
    {
        try {
            $host = rtrim((string) config('scout.meilisearch.host', env('MEILISEARCH_HOST')), '/');

            if ($host === '') {
                return 'error';
            }

            $response = Http::timeout(3)
                ->acceptJson()
                ->get($host . '/health');

            return $response->successful() ? 'ok' : 'error';
        } catch (Throwable $exception) {
            Log::warning('Health check meilisearch failed', [
                'service' => 'meilisearch',
                'error' => $exception->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Verifica disponibilidad del microservicio ML.
     *
     * @return string
     */
    private function checkMlService(): string
    {
        try {
            $baseUrl = rtrim((string) env('ML_SERVICE_URL'), '/');

            if ($baseUrl === '') {
                return 'error';
            }

            $response = Http::timeout((int) env('ML_TIMEOUT_SECONDS', 10))
                ->acceptJson()
                ->withToken((string) env('ML_SERVICE_TOKEN'))
                ->get($baseUrl . '/api/v1/health');

            return $response->successful() ? 'ok' : 'error';
        } catch (Throwable $exception) {
            Log::warning('Health check ml service failed', [
                'service' => 'ml_service',
                'error' => $exception->getMessage(),
            ]);

            return 'error';
        }
    }
}
