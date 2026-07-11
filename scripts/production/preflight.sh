#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.prod.yml"
COMPOSE_ENV="$ROOT_DIR/docker/env/compose.env"
SHARED_DIR="${ATLANTIA_SHARED_DIR:-/opt/atlantia/shared}"

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

check_secret_file "$SHARED_DIR/marketplace.env"
check_secret_file "$SHARED_DIR/ml.env"

docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" config --quiet
docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" pull --quiet app nginx ml-api ml-worker
docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE" run --rm --no-deps app php artisan about --only=environment

echo "Production preflight completed successfully."
