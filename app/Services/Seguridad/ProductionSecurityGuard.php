<?php

namespace App\Services\Seguridad;

use RuntimeException;

class ProductionSecurityGuard
{
    public function enforce(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $invalid = [];
        foreach (['session.encrypt', 'session.secure', 'session.http_only'] as $key) {
            if (config($key) !== true) {
                $invalid[] = $key;
            }
        }

        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) !== 'https'
            || ! parse_url((string) config('app.url'), PHP_URL_HOST)) {
            $invalid[] = 'APP_URL (HTTPS requerido)';
        }

        if (config('app.debug') || env('TRUSTED_PROXIES') === '*') {
            $invalid[] = 'APP_DEBUG / TRUSTED_PROXIES';
        }

        $origins = (array) config('cors.allowed_origins', []);
        if ($origins === [] || config('cors.allowed_origins_patterns', []) !== []) {
            $invalid[] = 'CORS_ALLOWED_ORIGINS';
        }
        foreach ($origins as $origin) {
            if (! is_string($origin) || str_contains($origin, '*')
                || ! in_array(parse_url($origin, PHP_URL_SCHEME), ['https', 'capacitor'], true)
                || ! parse_url($origin, PHP_URL_HOST)) {
                $invalid[] = 'CORS_ALLOWED_ORIGINS';
                break;
            }
        }

        if ($invalid !== []) {
            throw new RuntimeException('Configuracion insegura de produccion: '.implode(', ', array_unique($invalid)));
        }
    }
}
