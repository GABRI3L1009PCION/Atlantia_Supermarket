#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ -f artisan ]; then
    mkdir -p storage bootstrap/cache

    if [ "${APP_ENV:-production}" = "production" ] && [ "${APP_DEBUG:-false}" = "true" ]; then
        echo "APP_DEBUG=true is not allowed in production." >&2
        exit 1
    fi

    if [ "${APP_ENV:-production}" = "production" ] && [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY is required in production." >&2
        exit 1
    fi

    php artisan package:discover --ansi || true

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        php artisan migrate --force
    fi

    if [ "${RUN_OPTIMIZE:-false}" = "true" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
fi

exec "$@"
