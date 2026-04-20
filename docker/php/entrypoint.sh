#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ -f artisan ]; then
    mkdir -p storage bootstrap/cache
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
