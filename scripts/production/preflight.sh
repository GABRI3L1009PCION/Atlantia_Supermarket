#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.prod.yml"
COMPOSE_ENV="$ROOT_DIR/docker/env/compose.env"
SHARED_DIR="${ATLANTIA_SHARED_DIR:-/opt/atlantia/shared}"
export ATLANTIA_SHARED_DIR="$SHARED_DIR"

fail() {
    echo "Preflight failed: $1" >&2
    exit 1
}

require_file() {
    [ -f "$1" ] || fail "Required file does not exist: $1"
}

check_secret_file() {
    local path="$1"
    local mode

    require_file "$path"
    mode="$(stat -c '%a' "$path")"
    case "$mode" in
        400|600) ;;
        *) fail "$path must have permissions 400 or 600 (current: $mode)." ;;
    esac

    if grep -Eiq 'CHANGE_ME|example\.invalid|replace[_ -]?me' "$path"; then
        fail "$path still contains placeholder values."
    fi
}

command -v docker >/dev/null 2>&1 || fail "Docker is not installed."
docker info >/dev/null 2>&1 || fail "Docker daemon is not available."

require_file "$COMPOSE_ENV"
if grep -Eiq 'CHANGE_ME|example\.invalid|replace[_ -]?me' "$COMPOSE_ENV"; then
    fail "$COMPOSE_ENV still contains placeholder values."
fi

# compose.env no tiene secretos: se exporta para que COMPOSE_PROFILES (p. ej. stateful-local) aplique.
set -a
# shellcheck disable=SC1090
. "$COMPOSE_ENV"
set +a

check_secret_file "$SHARED_DIR/marketplace.env"
check_secret_file "$SHARED_DIR/integrations.env"
check_secret_file "$SHARED_DIR/ml.env"

case ",${COMPOSE_PROFILES:-}," in
    *,stateful-local,*)
        check_secret_file "$SHARED_DIR/mysql.env"
        check_secret_file "$SHARED_DIR/redis.env"
        check_secret_file "$SHARED_DIR/search.env"
        ;;
esac

docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" config --quiet
docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" pull --quiet app nginx ml-api ml-worker
docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" run --rm --no-deps app php artisan about --only=environment
if [ "${ATLANTIA_PREFLIGHT_STRICT_INTEGRATIONS:-false}" = "true" ]; then
    docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" run --rm --no-deps app php artisan atlantia:integrations-readiness
elif ! docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" run --rm --no-deps app php artisan atlantia:integrations-readiness; then
    echo "Preflight warning: some external integrations are pending (see table above). Continuing because ATLANTIA_PREFLIGHT_STRICT_INTEGRATIONS is not true." >&2
fi

echo "Production preflight completed successfully."
