#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.prod.yml"
COMPOSE_ENV="$ROOT_DIR/docker/env/compose.env"

"$ROOT_DIR/scripts/production/preflight.sh"

compose=(docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE")

"${compose[@]}" pull
"${compose[@]}" run --rm app php artisan migrate --force --isolated
"${compose[@]}" run --rm app php artisan scout:sync-index-settings
"${compose[@]}" up -d --remove-orphans --wait
"${compose[@]}" exec -T app php artisan queue:restart

domain="$(sed -n 's/^ATLANTIA_DOMAIN=//p' "$COMPOSE_ENV" | tail -n 1)"
[ -n "$domain" ] || { echo "ATLANTIA_DOMAIN is missing." >&2; exit 1; }

curl --fail --silent --show-error --retry 8 --retry-delay 5 "https://$domain/health" >/dev/null
"${compose[@]}" ps

echo "Production deployment completed and health endpoint is responding."
