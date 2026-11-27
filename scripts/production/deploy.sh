#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.prod.yml"
COMPOSE_ENV="$ROOT_DIR/docker/env/compose.env"

"$ROOT_DIR/scripts/production/preflight.sh"

# compose.env no tiene secretos: se exporta para que COMPOSE_PROFILES aplique.
set -a
# shellcheck disable=SC1090
. "$COMPOSE_ENV"
set +a

compose=(docker compose --env-file "$COMPOSE_ENV" -f "$COMPOSE_FILE")

"${compose[@]}" pull

# Con el perfil stateful-local, MySQL/Redis/Meilisearch viven en este servidor y deben
# estar sanos antes de migrar.
case ",${COMPOSE_PROFILES:-}," in
    *,stateful-local,*)
        "${compose[@]}" up -d --wait mysql redis meilisearch
        ;;
esac

"${compose[@]}" run --rm app php artisan migrate --force --isolated
# Llaves OAuth de Passport (app del repartidor): se crean una sola vez en el volumen de storage.
"${compose[@]}" run --rm app sh -c '[ -f storage/oauth-private.key ] || php artisan passport:keys'
"${compose[@]}" run --rm app php artisan scout:sync-index-settings
"${compose[@]}" up -d --remove-orphans --wait
"${compose[@]}" exec -T app php artisan queue:restart

domain="${ATLANTIA_DOMAIN:-}"
[ -n "$domain" ] || { echo "ATLANTIA_DOMAIN is missing." >&2; exit 1; }

curl --fail --silent --show-error --retry 8 --retry-delay 5 "https://$domain/health" >/dev/null
"${compose[@]}" ps

echo "Production deployment completed and health endpoint is responding."
