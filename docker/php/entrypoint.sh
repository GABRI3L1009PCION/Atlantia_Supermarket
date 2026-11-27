#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

fail() {
    echo "Production preflight failed: $1" >&2
    exit 1
}

is_placeholder() {
    case "${1:-}" in
        ""|*CHANGE_ME*|*change_me*|*example.invalid*) return 0 ;;
        *) return 1 ;;
    esac
}

require_value() {
    local name="$1"
    local value="${!name:-}"

    # Con "set -e", un "is_placeholder && fail" como ultima linea devuelve 1 cuando el valor es
    # valido y detenia el contenedor sin mensaje; por eso se usa un if explicito.
    if is_placeholder "$value"; then
        fail "$name is missing or still uses a placeholder."
    fi
}

require_equals() {
    local name="$1"
    local expected="$2"
    local value="${!name:-}"

    [ "$value" = "$expected" ] || fail "$name must be '$expected'."
}

require_min_length() {
    local name="$1"
    local minimum="$2"
    local value="${!name:-}"

    [ "${#value}" -ge "$minimum" ] || fail "$name must contain at least $minimum characters."
}

require_unique_secrets() {
    local first_name
    local second_name
    local first_value
    local second_value

    for first_name in "$@"; do
        first_value="${!first_name:-}"
        for second_name in "$@"; do
            [ "$first_name" = "$second_name" ] && continue
            second_value="${!second_name:-}"
            [ "$first_value" != "$second_value" ] || fail "$first_name and $second_name must use different secrets."
        done
    done
}

production_preflight() {
    case "${APP_DEBUG:-false}" in
        true|TRUE|True|1) fail "APP_DEBUG=true is not allowed." ;;
    esac

    require_value APP_KEY
    require_value APP_URL
    case "$APP_URL" in
        https://*) ;;
        *) fail "APP_URL must use HTTPS." ;;
    esac

    require_value TRUSTED_PROXIES
    [ "$TRUSTED_PROXIES" != "*" ] || fail "TRUSTED_PROXIES cannot trust every proxy."
    require_equals SESSION_SECURE_COOKIE true

    require_equals DB_CONNECTION mysql
    require_value DB_HOST
    require_value DB_DATABASE
    require_value DB_USERNAME
    require_value ATLANTIA_DB_PASSWORD
    require_min_length ATLANTIA_DB_PASSWORD 24

    require_equals CACHE_STORE redis
    require_equals QUEUE_CONNECTION redis
    require_equals SESSION_DRIVER redis
    require_value REDIS_HOST
    require_value REDIS_PASSWORD
    require_min_length REDIS_PASSWORD 32
    if [ "${REDIS_SCHEME:-tcp}" != "tls" ] && [ "$REDIS_HOST" != "redis" ]; then
        fail "REDIS_SCHEME must be 'tls' for a remote Redis service."
    fi

    # Archivos: S3 o disco local persistente (volumen app-storage). S3 solo se valida si se usa.
    case "${FILESYSTEM_DISK:-local}:${PRIVATE_FILESYSTEM_DISK:-local}" in
        *s3*)
            require_value AWS_DEFAULT_REGION
            require_value AWS_BUCKET
            if [ "${AWS_USE_INSTANCE_PROFILE:-false}" != "true" ]; then
                require_value AWS_ACCESS_KEY_ID
                require_value AWS_SECRET_ACCESS_KEY
            fi
            ;;
    esac

    require_equals MAIL_MAILER smtp
    require_value MAIL_SCHEME
    require_value MAIL_HOST
    require_value MAIL_USERNAME
    require_value ATLANTIA_MAIL_APP_PASSWORD
    require_value MAIL_FROM_ADDRESS

    require_equals SCOUT_DRIVER meilisearch
    require_value MEILISEARCH_HOST
    require_value MEILISEARCH_KEY
    require_min_length MEILISEARCH_KEY 24

    # FEL INFILE es opcional: sin credenciales las tiendas registran su factura SAT manualmente.
    case "${INFILE_MOCK:-false}" in
        true|TRUE|True|1) fail "INFILE_MOCK=true is not allowed in production (would issue fake invoices)." ;;
    esac
    if ! is_placeholder "${INFILE_USERNAME:-}"; then
        require_value INFILE_PASSWORD
    fi
    require_value INFILE_WEBHOOK_SECRET
    require_value PAYMENT_GATEWAY_WEBHOOK_SECRET
    if [ "${ATLANTIA_CARD_PAYMENT_MODE:-pos}" = "recurrente" ]; then
        require_value RECURRENTE_SECRET_KEY
        require_value RECURRENTE_WEBHOOK_SECRET
    fi
    require_value ML_SERVICE_URL
    require_value ML_SERVICE_TOKEN
    require_value ML_WEBHOOK_SECRET
    require_value COURIER_WEBHOOK_SECRET
    # Mapbox, Google Maps y reCAPTCHA son recomendados pero no bloquean el arranque:
    # sin ellos el sistema usa sus alternativas (rutas OSRM, mapas sin selector, sin reCAPTCHA).
    for optional in ATLANTIA_MAPBOX_TOKEN GOOGLE_MAPS_API_KEY RECAPTCHA_SITE_KEY RECAPTCHA_SECRET_KEY; do
        if is_placeholder "${!optional:-}"; then
            echo "Production warning: $optional is not configured." >&2
        fi
    done

    require_min_length INFILE_WEBHOOK_SECRET 32
    require_min_length PAYMENT_GATEWAY_WEBHOOK_SECRET 32
    require_min_length ML_SERVICE_TOKEN 32
    require_min_length ML_WEBHOOK_SECRET 32
    require_min_length COURIER_WEBHOOK_SECRET 32
    require_unique_secrets INFILE_WEBHOOK_SECRET PAYMENT_GATEWAY_WEBHOOK_SECRET ML_SERVICE_TOKEN ML_WEBHOOK_SECRET COURIER_WEBHOOK_SECRET
}

if [ -f artisan ]; then
    mkdir -p storage bootstrap/cache

    if [ "${APP_ENV:-production}" = "production" ]; then
        production_preflight
    fi

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
