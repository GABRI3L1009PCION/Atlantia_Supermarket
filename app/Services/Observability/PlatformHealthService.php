<?php

namespace App\Services\Observability;

use App\Models\AuditLog;
use App\Models\Ml\MlTrainingJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Consolida salud tecnica, alertas operativas y contexto de despliegue.
 */
class PlatformHealthService
{
    /**
     * Estado publico para uptime y balanceadores.
     *
     * @return array<string, mixed>
     */
    public function publicHealth(): array
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'redis' => $this->redisCheck(),
            'meilisearch' => $this->meilisearchCheck(),
            'ml_service' => $this->mlServiceCheck(),
            'firebase_push' => $this->firebasePushCheck(),
            'maps_config' => $this->mapsConfigCheck(),
            'support_center' => $this->supportCenterCheck(),
            'onsite_payments' => $this->onsitePaymentsCheck(),
        ];

        $status = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')
            ? 'degraded'
            : 'ok';

        return [
            'status' => $status,
            'database' => $checks['database']['status'],
            'redis' => $checks['redis']['status'],
            'meilisearch' => $checks['meilisearch']['status'],
            'ml_service' => $checks['ml_service']['status'],
            'firebase_push' => $checks['firebase_push']['status'],
            'maps_config' => $checks['maps_config']['status'],
            'support_center' => $checks['support_center']['status'],
            'onsite_payments' => $checks['onsite_payments']['status'],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Estado extendido para observabilidad y respuesta a incidentes.
     *
     * @return array<string, mixed>
     */
    public function operationsSnapshot(): array
    {
        $checks = collect([
            $this->databaseCheck(),
            $this->redisCheck(),
            $this->meilisearchCheck(),
            $this->mlServiceCheck(),
            $this->firebasePushCheck(),
            $this->mapsConfigCheck(),
            $this->supportCenterCheck(),
            $this->onsitePaymentsCheck(),
            $this->queueCheck(),
            $this->schedulerHeartbeatCheck(),
            $this->storageCheck(),
            $this->backupCheck(),
        ]);

        $status = $this->aggregateStatus($checks->all());
        $failedJobs = $this->failedJobsCount();
        $latestBackup = $this->latestBackup();

        return [
            'status' => $status,
            'generated_at' => now()->toIso8601String(),
            'checks' => $checks->values()->all(),
            'summary' => [
                'ok' => $checks->where('status', 'ok')->count(),
                'warning' => $checks->where('status', 'warning')->count(),
                'error' => $checks->where('status', 'error')->count(),
                'failed_jobs' => $failedJobs,
                'audit_events_24h' => $this->auditEventsLast24Hours(),
                'ml_failed_jobs_24h' => $this->mlFailedJobsLast24Hours(),
                'latest_backup' => $latestBackup,
            ],
            'incidents' => $this->incidentCards($checks->all(), $failedJobs, $latestBackup),
            'response_steps' => $this->responseSteps($status),
            'release' => [
                'environment' => app()->environment(),
                'version' => (string) config('app.version', '1.0.0'),
                'app_url' => (string) config('app.url'),
                'healthcheck_url' => url('/health'),
                'staging_url' => (string) config('observability.release.staging_url'),
                'status_page_url' => (string) config('observability.release.status_page_url'),
                'runbook_path' => (string) config('observability.release.runbook_path'),
                'log_channel' => (string) config('observability.alerts.channel', 'incidents'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->makeCheck('database', 'Base de datos', 'ok', 'Conexion MySQL disponible.');
        } catch (Throwable $exception) {
            return $this->makeCheck('database', 'Base de datos', 'error', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function redisCheck(): array
    {
        try {
            $response = Redis::connection(config('database.redis.cache.connection', 'cache'))->ping();
            $responseText = is_bool($response) ? $response : strtoupper((string) $response);

            if (in_array($responseText, [true, '+PONG', 'PONG'], true)) {
                return $this->makeCheck('redis', 'Redis', 'ok', 'Cache y sesiones respondiendo.');
            }

            return $this->makeCheck('redis', 'Redis', 'error', 'Redis respondio sin PONG.');
        } catch (Throwable $exception) {
            return $this->makeCheck('redis', 'Redis', 'error', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function meilisearchCheck(): array
    {
        try {
            $host = rtrim((string) config('scout.meilisearch.host', env('MEILISEARCH_HOST')), '/');

            if ($host === '') {
                return $this->makeCheck('meilisearch', 'Busqueda', 'warning', 'Host de Meilisearch no configurado.');
            }

            $response = Http::timeout(3)
                ->acceptJson()
                ->get($host.'/health');

            return $response->successful()
                ? $this->makeCheck('meilisearch', 'Busqueda', 'ok', 'Indice de catalogo operativo.')
                : $this->makeCheck('meilisearch', 'Busqueda', 'error', 'Meilisearch respondio con error.');
        } catch (Throwable $exception) {
            return $this->makeCheck('meilisearch', 'Busqueda', 'error', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mlServiceCheck(): array
    {
        try {
            $baseUrl = rtrim((string) config('services.ml.base_url'), '/');

            if ($baseUrl === '') {
                return $this->makeCheck('ml_service', 'Microservicio ML', 'warning', 'ML_SERVICE_URL no configurado.');
            }

            $response = Http::timeout((int) env('ML_TIMEOUT_SECONDS', 10))
                ->acceptJson()
                ->withToken((string) env('ML_SERVICE_TOKEN'))
                ->get($baseUrl.'/health');

            return $response->successful()
                ? $this->makeCheck('ml_service', 'Microservicio ML', 'ok', 'Predicciones y fraude disponibles.')
                : $this->makeCheck('ml_service', 'Microservicio ML', 'error', 'ML respondio con error.');
        } catch (Throwable $exception) {
            return $this->makeCheck('ml_service', 'Microservicio ML', 'error', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function firebasePushCheck(): array
    {
        $enabled = (bool) config('services.firebase.enabled', false);
        $projectId = trim((string) config('services.firebase.project_id'));
        $serviceAccount = trim((string) config('services.firebase.service_account_email'));
        $privateKey = trim((string) config('services.firebase.private_key'));

        if (! $enabled) {
            return $this->makeCheck('firebase_push', 'Push FCM', 'warning', 'Firebase esta deshabilitado.');
        }

        return $projectId !== '' && $serviceAccount !== '' && $privateKey !== ''
            ? $this->makeCheck('firebase_push', 'Push FCM', 'ok', 'Credenciales FCM completas.')
            : $this->makeCheck('firebase_push', 'Push FCM', 'error', 'Faltan credenciales FCM definitivas.');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapsConfigCheck(): array
    {
        $mapboxToken = trim((string) config('services.mapbox.token'));
        $googleMapsKey = trim((string) config('services.google_maps.api_key'));

        return $mapboxToken !== '' || $googleMapsKey !== ''
            ? $this->makeCheck('maps_config', 'Mapas y navegacion', 'ok', 'Al menos una llave de mapas esta configurada.')
            : $this->makeCheck('maps_config', 'Mapas y navegacion', 'warning', 'No hay llaves reales de mapas.');
    }

    /**
     * @return array<string, mixed>
     */
    private function supportCenterCheck(): array
    {
        $phone = trim((string) config('atlantia.support.phone'));
        $emergencyPhone = trim((string) config('atlantia.support.emergency_phone'));
        $channels = config('atlantia.support.channels', []);

        return $phone !== '' && $emergencyPhone !== '' && is_array($channels) && $channels !== []
            ? $this->makeCheck('support_center', 'Centro de soporte', 'ok', 'Canales de soporte configurados.')
            : $this->makeCheck('support_center', 'Centro de soporte', 'warning', 'Soporte operativo incompleto.');
    }

    /**
     * @return array<string, mixed>
     */
    private function onsitePaymentsCheck(): array
    {
        $posProvider = trim((string) config('atlantia.payments.pos.provider'));
        $transferBank = trim((string) config('atlantia.payments.transfer.bank_name'));
        $transferAccount = trim((string) config('atlantia.payments.transfer.account_number'));

        return $posProvider !== '' && $transferBank !== '' && $transferAccount !== ''
            ? $this->makeCheck('onsite_payments', 'Cobros presenciales', 'ok', 'POS y transferencia configurados.')
            : $this->makeCheck('onsite_payments', 'Cobros presenciales', 'warning', 'Falta completar cobros contra entrega.');
    }

    /**
     * @return array<string, mixed>
     */
    private function queueCheck(): array
    {
        $driver = (string) config('queue.default', 'sync');
        $failedJobs = $this->failedJobsCount();
        $warningThreshold = (int) config('observability.queue.warning_failed_jobs', 1);
        $errorThreshold = (int) config('observability.queue.error_failed_jobs', 10);

        if ($driver === 'sync') {
            return $this->makeCheck(
                'queue',
                'Colas y workers',
                'warning',
                'La cola esta en modo sync; no es apto para produccion.',
                ['failed_jobs' => $failedJobs]
            );
        }

        if ($failedJobs >= $errorThreshold) {
            return $this->makeCheck(
                'queue',
                'Colas y workers',
                'error',
                "Hay {$failedJobs} jobs fallidos acumulados.",
                ['failed_jobs' => $failedJobs]
            );
        }

        if ($failedJobs >= $warningThreshold) {
            return $this->makeCheck(
                'queue',
                'Colas y workers',
                'warning',
                "Hay {$failedJobs} jobs fallidos que requieren revision.",
                ['failed_jobs' => $failedJobs]
            );
        }

        return $this->makeCheck(
            'queue',
            'Colas y workers',
            'ok',
            "Driver {$driver} sin fallos acumulados.",
            ['failed_jobs' => $failedJobs]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulerHeartbeatCheck(): array
    {
        if (app()->environment(['local', 'testing'])) {
            return $this->makeCheck('scheduler', 'Scheduler', 'ok', 'No se exige heartbeat en entorno local/testing.');
        }

        $key = (string) config('observability.scheduler.heartbeat_key');
        $raw = Cache::get($key);

        if (! is_string($raw) || trim($raw) === '') {
            return $this->makeCheck('scheduler', 'Scheduler', 'warning', 'No existe heartbeat reciente del scheduler.');
        }

        try {
            $lastBeat = CarbonImmutable::parse($raw);
            $minutes = $lastBeat->diffInMinutes(now());
            $allowed = (int) config('observability.scheduler.stale_after_minutes', 3);

            return $minutes <= $allowed
                ? $this->makeCheck('scheduler', 'Scheduler', 'ok', "Ultimo latido hace {$minutes} min.", ['last_beat' => $raw])
                : $this->makeCheck('scheduler', 'Scheduler', 'warning', "Heartbeat atrasado {$minutes} min.", ['last_beat' => $raw]);
        } catch (Throwable) {
            return $this->makeCheck('scheduler', 'Scheduler', 'warning', 'El heartbeat guardado no es valido.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storageCheck(): array
    {
        $logsPath = storage_path('logs');
        $backupsPath = (string) config('atlantia.backups.directory', storage_path('app/backups'));

        $logsWritable = File::exists($logsPath) && File::isWritable($logsPath);
        $backupWritable = File::exists($backupsPath) ? File::isWritable($backupsPath) : File::isDirectory(dirname($backupsPath));

        if (! $logsWritable) {
            return $this->makeCheck('storage', 'Logs y storage', 'error', 'storage/logs no es escribible.');
        }

        if (! $backupWritable) {
            return $this->makeCheck('storage', 'Logs y storage', 'warning', 'La ruta de respaldos no esta lista para escritura.');
        }

        return $this->makeCheck('storage', 'Logs y storage', 'ok', 'Logs y respaldos con permisos operativos.');
    }

    /**
     * @return array<string, mixed>
     */
    private function backupCheck(): array
    {
        if (app()->environment(['local', 'testing'])) {
            return $this->makeCheck('backups', 'Respaldos', 'ok', 'Respaldo continuo no exigido en entorno local/testing.');
        }

        $latestBackup = $this->latestBackup();
        $latestTimestamp = $latestBackup['timestamp'] ?? null;

        if (! is_string($latestTimestamp) || $latestTimestamp === '') {
            return $this->makeCheck('backups', 'Respaldos', 'warning', 'No se encontro ningun respaldo reciente.', $latestBackup);
        }

        $hours = (int) ($latestBackup['age_hours'] ?? 0);
        $warningHours = (int) config('observability.backups.warning_hours', 26);
        $errorHours = (int) config('observability.backups.error_hours', 50);

        if ($hours >= $errorHours) {
            return $this->makeCheck('backups', 'Respaldos', 'error', "El ultimo respaldo tiene {$hours} horas.", $latestBackup);
        }

        if ($hours >= $warningHours) {
            return $this->makeCheck('backups', 'Respaldos', 'warning', "El ultimo respaldo tiene {$hours} horas.", $latestBackup);
        }

        return $this->makeCheck('backups', 'Respaldos', 'ok', "Ultimo respaldo hace {$hours} horas.", $latestBackup);
    }

    /**
     * @return array<string, mixed>
     */
    private function latestBackup(): array
    {
        $directory = (string) config('atlantia.backups.directory', storage_path('app/backups'));

        if (! File::exists($directory) || ! File::isDirectory($directory)) {
            return ['path' => $directory];
        }

        $files = collect(File::files($directory))
            ->filter(fn ($file): bool => $file->isFile())
            ->sortByDesc(fn ($file): int => $file->getMTime());

        $latest = $files->first();

        if ($latest === null) {
            return ['path' => $directory];
        }

        $timestamp = CarbonImmutable::createFromTimestamp($latest->getMTime());

        return [
            'path' => $latest->getPathname(),
            'timestamp' => $timestamp->toIso8601String(),
            'age_hours' => $timestamp->diffInHours(now()),
            'size_mb' => round(($latest->getSize() / 1024 / 1024), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCheck(
        string $key,
        string $label,
        string $status,
        string $detail,
        array $meta = [],
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     */
    private function aggregateStatus(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function auditEventsLast24Hours(): int
    {
        if (! Schema::hasTable('audit_logs')) {
            return 0;
        }

        return AuditLog::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    private function mlFailedJobsLast24Hours(): int
    {
        if (! Schema::hasTable('ml_training_jobs')) {
            return 0;
        }

        return MlTrainingJob::query()
            ->where('estado', 'fallido')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<int, array<string, mixed>>
     */
    private function incidentCards(array $checks, int $failedJobs, array $latestBackup): array
    {
        $cards = collect($checks)
            ->filter(fn (array $check): bool => in_array($check['status'], ['warning', 'error'], true))
            ->map(function (array $check): array {
                $action = match ($check['key']) {
                    'database' => 'Verificar conectividad MySQL, credenciales, TLS y limite de conexiones.',
                    'redis' => 'Revisar Redis, sesiones y workers que dependan de cache o cola.',
                    'meilisearch' => 'Comprobar indice, clave maestra y conectividad privada de busqueda.',
                    'ml_service' => 'Validar ML, tokens y cola asociada a fraude o predicciones.',
                    'firebase_push' => 'Completar credenciales FCM y probar push con app cerrada.',
                    'queue' => 'Inspeccionar failed jobs, reiniciar workers y drenar colas atascadas.',
                    'scheduler' => 'Comprobar schedule:work, locking y latido del scheduler.',
                    'backups' => 'Generar respaldo inmediato y revisar rotacion o subida externa.',
                    default => 'Revisar configuracion y corregir antes del siguiente despliegue.',
                };

                return [
                    'title' => $check['label'],
                    'status' => $check['status'],
                    'detail' => $check['detail'],
                    'action' => $action,
                ];
            });

        if ($failedJobs > 0) {
            $cards->push([
                'title' => 'Jobs fallidos',
                'status' => $failedJobs >= (int) config('observability.queue.error_failed_jobs', 10) ? 'error' : 'warning',
                'detail' => "Hay {$failedJobs} jobs fallidos pendientes de resolver.",
                'action' => 'Ejecutar queue:retry selectivo o depurar el job antes de reintentarlo.',
            ]);
        }

        if (($latestBackup['path'] ?? null) !== null && ($latestBackup['age_hours'] ?? null) !== null) {
            $cards->push([
                'title' => 'Ultimo respaldo',
                'status' => ((int) $latestBackup['age_hours']) > (int) config('observability.backups.warning_hours', 26) ? 'warning' : 'ok',
                'detail' => "Archivo {$latestBackup['path']} con antiguedad de {$latestBackup['age_hours']} horas.",
                'action' => 'Confirmar restauracion de muestra y continuidad del cron de backup.',
            ]);
        }

        return $cards->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function responseSteps(string $status): array
    {
        return match ($status) {
            'error' => [
                'Confirmar el alcance del incidente y congelar despliegues nuevos.',
                'Revisar /health, jobs fallidos, scheduler y logs centralizados.',
                'Aplicar mitigacion: reinicio de workers, rollback o mantenimiento controlado.',
                'Registrar causa, hora, impacto y responsables en auditoria operativa.',
            ],
            'warning' => [
                'Atender alertas preventivas antes de que impacten pedidos en vivo.',
                'Revisar credenciales faltantes, backups atrasados y jobs fallidos.',
                'Verificar que staging siga alineado con produccion antes del siguiente release.',
            ],
            default => [
                'Mantener monitoreo activo de salud y alertas cada 5 minutos.',
                'Probar restauracion, push FCM y despliegue en staging al menos una vez por semana.',
                'Guardar evidencia de pruebas criticas antes de liberar una nueva version.',
            ],
        };
    }
}
