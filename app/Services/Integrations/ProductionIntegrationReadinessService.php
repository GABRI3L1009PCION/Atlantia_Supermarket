<?php

namespace App\Services\Integrations;

use App\Services\Notificaciones\FirebasePushService;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Audita configuracion y conectividad sin exponer credenciales.
 */
class ProductionIntegrationReadinessService
{
    public function __construct(
        private readonly FirebasePushService $firebasePushService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(bool $probe = false): array
    {
        $checks = [
            $this->infileCheck($probe),
            $this->smtpCheck($probe),
            $this->s3Check($probe),
            $this->firebaseCheck($probe),
            $this->mapsCheck($probe),
            $this->supportCheck(),
            $this->posCheck(),
        ];

        $status = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')
            ? 'error'
            : (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')
                ? 'warning'
                : 'ok');

        return [
            'status' => $status,
            'probe_enabled' => $probe,
            'generated_at' => now()->toIso8601String(),
            'checks' => $checks,
            'summary' => [
                'ok' => collect($checks)->where('status', 'ok')->count(),
                'warning' => collect($checks)->where('status', 'warning')->count(),
                'error' => collect($checks)->where('status', 'error')->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function infileCheck(bool $probe): array
    {
        $baseUrl = trim((string) config('services.infile.base_url'));
        $username = trim((string) config('services.infile.username'));
        $password = trim((string) config('services.infile.password'));
        $webhookSecret = trim((string) config('services.infile.webhook_secret'));
        $missing = $this->missing([
            'INFILE_BASE_URL' => $baseUrl,
            'INFILE_USERNAME' => $username,
            'INFILE_PASSWORD' => $password,
            'INFILE_WEBHOOK_SECRET' => $webhookSecret,
        ]);
        $invalid = [];

        if ((bool) config('services.infile.mock')) {
            $invalid[] = 'INFILE_MOCK debe ser false fuera de pruebas.';
        }

        if ($this->usable($baseUrl) && ! $this->isHttpsUrl($baseUrl)) {
            $invalid[] = 'INFILE_BASE_URL debe usar HTTPS.';
        }

        if ($this->usable($webhookSecret) && strlen($webhookSecret) < 32) {
            $invalid[] = 'INFILE_WEBHOOK_SECRET debe tener al menos 32 caracteres.';
        }

        return $this->result(
            'infile',
            'FEL INFILE',
            $missing,
            $invalid,
            $probe && $missing === [] && $invalid === []
                ? $this->probeInfile($baseUrl)
                : null,
            ['Las credenciales FEL de cada vendedor pueden reemplazar la cuenta global desde su perfil fiscal.']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function smtpCheck(bool $probe): array
    {
        $mailer = trim((string) config('mail.default'));
        $host = trim((string) config('mail.mailers.smtp.host'));
        $port = (int) config('mail.mailers.smtp.port');
        $username = trim((string) config('mail.mailers.smtp.username'));
        $password = trim((string) config('mail.mailers.smtp.password'));
        $from = trim((string) config('mail.from.address'));
        $missing = $this->missing([
            'MAIL_HOST' => $host,
            'MAIL_USERNAME' => $username,
            'ATLANTIA_MAIL_APP_PASSWORD' => $password,
            'MAIL_FROM_ADDRESS' => $from,
        ]);
        $invalid = [];

        if (! in_array($mailer, ['smtp', 'failover'], true)) {
            $invalid[] = 'MAIL_MAILER debe ser smtp o failover.';
        }

        if ($port < 1 || $port > 65535) {
            $invalid[] = 'MAIL_PORT no es valido.';
        }

        if ($this->usable($from) && ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $invalid[] = 'MAIL_FROM_ADDRESS no es un correo valido.';
        }

        return $this->result(
            'smtp',
            'Correo SMTP',
            $missing,
            $invalid,
            $probe && $missing === [] && $invalid === []
                ? $this->probeSmtp($host, $port)
                : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function s3Check(bool $probe): array
    {
        $disk = trim((string) config('filesystems.default'));
        $privateDisk = trim((string) config('filesystems.private_disk'));
        $config = (array) config('filesystems.disks.s3', []);
        $instanceProfile = (bool) ($config['use_instance_profile'] ?? false);
        $missing = $this->missing([
            'AWS_DEFAULT_REGION' => $config['region'] ?? null,
            'AWS_BUCKET' => $config['bucket'] ?? null,
        ]);

        if (! $instanceProfile) {
            $missing = array_merge($missing, $this->missing([
                'AWS_ACCESS_KEY_ID' => $config['key'] ?? null,
                'AWS_SECRET_ACCESS_KEY' => $config['secret'] ?? null,
            ]));
        }

        $invalid = [];
        if ($disk !== 's3' || $privateDisk !== 's3') {
            $invalid[] = 'FILESYSTEM_DISK y PRIVATE_FILESYSTEM_DISK deben ser s3.';
        }

        return $this->result(
            's3',
            'Almacenamiento S3',
            array_values(array_unique($missing)),
            $invalid,
            $probe && $missing === [] && $invalid === []
                ? $this->probeS3($config, $instanceProfile)
                : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function firebaseCheck(bool $probe): array
    {
        $enabled = (bool) config('services.firebase.enabled');
        $projectId = trim((string) config('services.firebase.project_id'));
        $email = trim((string) config('services.firebase.service_account_email'));
        $privateKey = str_replace('\n', "\n", trim((string) config('services.firebase.private_key')));
        $missing = $this->missing([
            'FIREBASE_PROJECT_ID' => $projectId,
            'FIREBASE_SERVICE_ACCOUNT_EMAIL' => $email,
            'FIREBASE_PRIVATE_KEY' => $privateKey,
        ]);
        $invalid = [];

        if (! $enabled) {
            $invalid[] = 'FIREBASE_ENABLED debe ser true.';
        }

        if ($this->usable($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid[] = 'FIREBASE_SERVICE_ACCOUNT_EMAIL no es valido.';
        }

        if ($this->usable($privateKey)
            && (! str_contains($privateKey, 'BEGIN PRIVATE KEY')
                || ! str_contains($privateKey, 'END PRIVATE KEY'))) {
            $invalid[] = 'FIREBASE_PRIVATE_KEY no tiene formato PEM.';
        }

        return $this->result(
            'fcm',
            'Firebase Cloud Messaging',
            $missing,
            $invalid,
            $probe && $missing === [] && $invalid === []
                ? $this->probeFirebase()
                : null,
            ['La app Android tambien requiere su archivo app/google-services.json del mismo proyecto Firebase.']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function mapsCheck(bool $probe): array
    {
        $mapboxToken = trim((string) config('services.mapbox.token'));
        $googleKey = trim((string) config('services.google_maps.api_key'));
        $missing = $this->missing([
            'ATLANTIA_MAPBOX_TOKEN' => $mapboxToken,
            'GOOGLE_MAPS_API_KEY' => $googleKey,
        ]);
        $invalid = [];

        if ($this->usable($mapboxToken) && ! str_starts_with($mapboxToken, 'pk.')) {
            $invalid[] = 'ATLANTIA_MAPBOX_TOKEN debe ser un token publico pk.* restringido.';
        }

        return $this->result(
            'maps',
            'Mapbox y Google Maps',
            $missing,
            $invalid,
            $probe && $missing === [] && $invalid === []
                ? $this->probeMapbox($mapboxToken)
                : null,
            ['Restringe Google Maps por paquete/SHA-256 y Mapbox por URL o aplicacion antes de produccion.']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function supportCheck(): array
    {
        $email = trim((string) config('atlantia.support.email'));
        $phone = trim((string) config('atlantia.support.phone'));
        $emergencyPhone = trim((string) config('atlantia.support.emergency_phone'));
        $whatsapp = trim((string) config('atlantia.support.whatsapp'));
        $channels = config('atlantia.support.channels', []);
        $missing = $this->missing([
            'ATLANTIA_SUPPORT_EMAIL' => $email,
            'ATLANTIA_SUPPORT_PHONE' => $phone,
            'ATLANTIA_SUPPORT_EMERGENCY_PHONE' => $emergencyPhone,
            'ATLANTIA_SUPPORT_WHATSAPP' => $whatsapp,
        ]);
        $invalid = [];

        if ($this->usable($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid[] = 'ATLANTIA_SUPPORT_EMAIL no es valido.';
        }

        if (! is_array($channels) || $channels === []) {
            $invalid[] = 'ATLANTIA_SUPPORT_CHANNELS debe incluir canales operativos.';
        }

        return $this->result('support', 'Centro de soporte', $missing, $invalid);
    }

    /**
     * @return array<string, mixed>
     */
    private function posCheck(): array
    {
        $enabled = (bool) config('atlantia.payments.pos.enabled');
        $provider = trim((string) config('atlantia.payments.pos.provider'));
        $supportPhone = trim((string) config('atlantia.payments.pos.support_phone'));
        $merchantId = trim((string) config('atlantia.payments.pos.merchant_id'));
        $terminalIds = config('atlantia.payments.pos.terminal_ids', []);
        $missing = $this->missing([
            'ATLANTIA_POS_PROVIDER' => $provider,
            'ATLANTIA_POS_SUPPORT_PHONE' => $supportPhone,
            'ATLANTIA_POS_MERCHANT_ID' => $merchantId,
        ]);
        $invalid = [];

        if (! $enabled) {
            $invalid[] = 'ATLANTIA_POS_ENABLED debe ser true.';
        }

        if (! is_array($terminalIds) || $terminalIds === []) {
            $missing[] = 'ATLANTIA_POS_TERMINAL_IDS';
        }

        return $this->result(
            'pos',
            'Terminales POS',
            array_values(array_unique($missing)),
            $invalid,
            null,
            ['Los identificadores se usan para conciliacion; no se exponen al cliente ni al repartidor.']
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    private function missing(array $values): array
    {
        return collect($values)
            ->filter(fn (mixed $value): bool => ! $this->usable($value))
            ->keys()
            ->values()
            ->all();
    }

    private function usable(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }

        $value = trim((string) $value);

        return $value !== ''
            && ! preg_match('/change[_ -]?me|replace[_ -]?me|your[-_]|example\.invalid/i', $value)
            && ! preg_match('/^\+?502[\s-]*5555/i', $value);
    }

    private function isHttpsUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https';
    }

    /**
     * @param  array<int, string>  $missing
     * @param  array<int, string>  $invalid
     * @param  array<string, mixed>|null  $probe
     * @param  array<int, string>  $notes
     * @return array<string, mixed>
     */
    private function result(
        string $key,
        string $label,
        array $missing,
        array $invalid,
        ?array $probe = null,
        array $notes = []
    ): array {
        $status = $missing !== [] || $invalid !== [] || ($probe !== null && ! $probe['ok'])
            ? 'error'
            : 'ok';

        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'missing' => $missing,
            'invalid' => $invalid,
            'probe' => $probe,
            'notes' => $notes,
        ];
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function probeInfile(string $baseUrl): array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get(rtrim($baseUrl, '/'));

            return [
                'ok' => $response->status() < 500,
                'detail' => $response->status() < 500
                    ? 'El servidor INFILE es alcanzable.'
                    : 'INFILE respondio con error de servidor.',
            ];
        } catch (Throwable) {
            return ['ok' => false, 'detail' => 'No fue posible conectar con INFILE.'];
        }
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function probeSmtp(string $host, int $port): array
    {
        $errorCode = 0;
        $errorMessage = '';
        $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 8);

        if (is_resource($connection)) {
            fclose($connection);

            return ['ok' => true, 'detail' => 'El servidor SMTP acepta conexiones TCP.'];
        }

        return ['ok' => false, 'detail' => 'No fue posible abrir conexion con el servidor SMTP.'];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{ok: bool, detail: string}
     */
    private function probeS3(array $config, bool $instanceProfile): array
    {
        try {
            $clientConfig = [
                'version' => 'latest',
                'region' => $config['region'],
                'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
            ];

            if (! empty($config['endpoint'])) {
                $clientConfig['endpoint'] = $config['endpoint'];
            }

            if (! $instanceProfile) {
                $clientConfig['credentials'] = [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ];
            }

            (new S3Client($clientConfig))->headBucket(['Bucket' => $config['bucket']]);

            return ['ok' => true, 'detail' => 'El bucket S3 es accesible.'];
        } catch (Throwable) {
            return ['ok' => false, 'detail' => 'No fue posible acceder al bucket S3.'];
        }
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function probeFirebase(): array
    {
        try {
            $authenticated = $this->firebasePushService->canAuthenticate();

            return [
                'ok' => $authenticated,
                'detail' => $authenticated
                    ? 'Firebase emitio un token OAuth valido.'
                    : 'Firebase rechazo las credenciales configuradas.',
            ];
        } catch (Throwable) {
            return ['ok' => false, 'detail' => 'No fue posible autenticar con Firebase.'];
        }
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function probeMapbox(string $token): array
    {
        try {
            $baseUrl = rtrim((string) config('services.mapbox.base_url', 'https://api.mapbox.com'), '/');
            $response = Http::timeout(8)->acceptJson()->get(
                $baseUrl.'/geocoding/v5/mapbox.places/Puerto%20Barrios.json',
                ['access_token' => $token, 'limit' => 1]
            );

            return [
                'ok' => $response->successful(),
                'detail' => $response->successful()
                    ? 'Mapbox acepto el token configurado.'
                    : 'Mapbox rechazo el token configurado.',
            ];
        } catch (Throwable) {
            return ['ok' => false, 'detail' => 'No fue posible conectar con Mapbox.'];
        }
    }
}
