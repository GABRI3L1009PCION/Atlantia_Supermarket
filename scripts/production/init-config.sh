#!/usr/bin/env bash
# Genera por primera vez la configuracion de produccion en este servidor.
#
#   bash scripts/production/init-config.sh <dominio> <tag-imagen> <correo-acme>
#   ej: bash scripts/production/init-config.sh store.atlantiasystems.com v1.0.1 operaciones@atlantiasystems.com
#
# - Todas las claves internas (APP_KEY, MySQL, Redis, Meilisearch, tokens y webhooks) se generan
#   aqui con openssl y nunca salen del servidor.
# - Base de datos, Redis y buscador corren en este mismo servidor (perfil stateful-local).
# - Fotos y documentos se guardan en el volumen local app-storage (S3 se puede activar despues).
# - FEL INFILE, Firebase, Mapbox y reCAPTCHA quedan apagados hasta tener credenciales reales.
# - Quedan marcados con CHANGE_ME solo los datos que debe poner una persona (Resend y Recurrente);
#   el preflight no deja desplegar mientras existan.
# - No sobrescribe archivos existentes (regenerar claves romperia la base de datos ya creada).
set -euo pipefail

DOMAIN="${1:-}"
IMAGE_TAG="${2:-}"
ACME_EMAIL="${3:-}"
[ -n "$DOMAIN" ] && [ -n "$IMAGE_TAG" ] && [ -n "$ACME_EMAIL" ] || {
    echo "Uso: $0 <dominio> <tag-imagen> <correo-acme>" >&2
    exit 1
}

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SHARED_DIR="${ATLANTIA_SHARED_DIR:-/opt/atlantia/shared}"
COMPOSE_ENV="$ROOT_DIR/docker/env/compose.env"
OWNER="$(git -C "$ROOT_DIR" config --get remote.origin.url | sed -E 's#.*github.com[:/]([^/]+)/.*#\1#' | tr '[:upper:]' '[:lower:]')"

command -v openssl >/dev/null || { echo "Falta openssl." >&2; exit 1; }

for f in "$COMPOSE_ENV" "$SHARED_DIR"/{marketplace,integrations,ml,mysql,redis,search}.env; do
    if [ -e "$f" ]; then
        echo "Ya existe $f. No se sobrescribe nada; edita ese archivo directamente." >&2
        exit 1
    fi
done

secret() { openssl rand -hex "${1:-32}"; }

# set_kv archivo CLAVE valor: reemplaza la linea CLAVE=... o la agrega. El valor viaja por
# ENVIRON para no depender de escapes de sed (las claves base64 contienen / + =).
set_kv() {
    local file="$1" key="$2"
    KV_KEY="$key" KV_VALUE="$3" awk '
        BEGIN { k = ENVIRON["KV_KEY"]; v = ENVIRON["KV_VALUE"]; done = 0 }
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' "$file" > "$file.tmp" && mv "$file.tmp" "$file"
}

APP_KEY="base64:$(openssl rand -base64 32)"
MYSQL_PASSWORD="$(secret 24)"
MYSQL_ROOT_PASSWORD="$(secret 32)"
REDIS_PASSWORD="$(secret 32)"
MEILI_KEY="$(secret 32)"
ML_SERVICE_TOKEN="$(secret 32)"
ML_WEBHOOK_SECRET="$(secret 32)"
COURIER_WEBHOOK_SECRET="$(secret 32)"
PAYMENT_GATEWAY_WEBHOOK_SECRET="$(secret 32)"
INFILE_WEBHOOK_SECRET="$(secret 32)"
MARKETPLACE_TOKEN="$(secret 32)"

umask 077
sudo install -d -m 700 -o "$(id -un)" -g "$(id -gn)" "$SHARED_DIR"

# ---------------------------------------------------------------- compose.env (sin secretos)
cp "$ROOT_DIR/docker/env/compose.env.example" "$COMPOSE_ENV"
set_kv "$COMPOSE_ENV" REGISTRY_IMAGE "ghcr.io/$OWNER/atlantia"
set_kv "$COMPOSE_ENV" APP_IMAGE_TAG "$IMAGE_TAG"
set_kv "$COMPOSE_ENV" ATLANTIA_DOMAIN "$DOMAIN"
set_kv "$COMPOSE_ENV" ACME_EMAIL "$ACME_EMAIL"
set_kv "$COMPOSE_ENV" MYSQL_BUFFER_POOL_SIZE 512M
set_kv "$COMPOSE_ENV" MYSQL_MAX_CONNECTIONS 150
set_kv "$COMPOSE_ENV" COMPOSE_PROFILES stateful-local
chmod 644 "$COMPOSE_ENV"

# ---------------------------------------------------------------- marketplace.env
M="$SHARED_DIR/marketplace.env"
sed "s/atlantiasupermarket\.com/$DOMAIN/g" "$ROOT_DIR/.env.production.example" > "$M"
set_kv "$M" APP_NAME '"Atlantia Store"'
set_kv "$M" APP_KEY "$APP_KEY"
set_kv "$M" APP_URL "https://$DOMAIN"
set_kv "$M" ATLANTIA_LOG_STACK daily,stderr
set_kv "$M" ATLANTIA_INCIDENT_CHANNELS stderr
set_kv "$M" DB_HOST mysql
set_kv "$M" ATLANTIA_DB_PASSWORD "$MYSQL_PASSWORD"
set_kv "$M" MYSQL_ATTR_SSL_CA ""
set_kv "$M" MYSQL_ATTR_SSL_VERIFY_SERVER_CERT false
set_kv "$M" SESSION_DOMAIN ""
set_kv "$M" REDIS_SCHEME tcp
set_kv "$M" REDIS_HOST redis
set_kv "$M" REDIS_PASSWORD "$REDIS_PASSWORD"
set_kv "$M" PUBLIC_DISK_URL "https://$DOMAIN/storage"
set_kv "$M" MEILISEARCH_HOST http://meilisearch:7700
set_kv "$M" MEILISEARCH_KEY "$MEILI_KEY"
set_kv "$M" PAYMENT_GATEWAY_WEBHOOK_SECRET "$PAYMENT_GATEWAY_WEBHOOK_SECRET"
set_kv "$M" RECURRENTE_SECRET_KEY CHANGE_ME_RECURRENTE_SK_LIVE
set_kv "$M" RECURRENTE_WEBHOOK_SECRET CHANGE_ME_RECURRENTE_WEBHOOK_SECRET
set_kv "$M" ML_SERVICE_TOKEN "$ML_SERVICE_TOKEN"
set_kv "$M" ML_WEBHOOK_SECRET "$ML_WEBHOOK_SECRET"
set_kv "$M" COURIER_WEBHOOK_SECRET "$COURIER_WEBHOOK_SECRET"
set_kv "$M" RECAPTCHA_SITE_KEY ""
set_kv "$M" RECAPTCHA_SECRET_KEY ""
set_kv "$M" ATLANTIA_SUPER_ADMIN_EMAIL ""
set_kv "$M" WEBPUSH_SUBJECT "mailto:soporte@atlantiasystems.com"
set_kv "$M" ATLANTIA_MEMBRESIA_ACTIVA false

# ---------------------------------------------------------------- integrations.env
I="$SHARED_DIR/integrations.env"
cp "$ROOT_DIR/docker/env/integrations.env.example" "$I"
set_kv "$I" INFILE_MOCK false
set_kv "$I" INFILE_USERNAME ""
set_kv "$I" INFILE_PASSWORD ""
set_kv "$I" INFILE_WEBHOOK_SECRET "$INFILE_WEBHOOK_SECRET"
# Correo por Resend (SMTP). El usuario SMTP de Resend es literalmente "resend".
set_kv "$I" MAIL_HOST smtp.resend.com
set_kv "$I" MAIL_PORT 587
set_kv "$I" MAIL_SCHEME smtp
set_kv "$I" MAIL_USERNAME resend
set_kv "$I" ATLANTIA_MAIL_APP_PASSWORD CHANGE_ME_RESEND_API_KEY
set_kv "$I" MAIL_EHLO_DOMAIN "$DOMAIN"
set_kv "$I" MAIL_FROM_ADDRESS no-reply@notificaciones.atlantiasystems.com
set_kv "$I" MAIL_FROM_NAME '"Atlantia Store"'
set_kv "$I" FILESYSTEM_DISK local
set_kv "$I" PRIVATE_FILESYSTEM_DISK local
set_kv "$I" AWS_ACCESS_KEY_ID ""
set_kv "$I" AWS_SECRET_ACCESS_KEY ""
set_kv "$I" AWS_DEFAULT_REGION us-east-2
set_kv "$I" AWS_BUCKET ""
set_kv "$I" FIREBASE_ENABLED false
set_kv "$I" FIREBASE_PROJECT_ID ""
set_kv "$I" FIREBASE_SERVICE_ACCOUNT_EMAIL ""
set_kv "$I" FIREBASE_PRIVATE_KEY ""
set_kv "$I" ATLANTIA_MAPBOX_TOKEN ""
set_kv "$I" GOOGLE_MAPS_API_KEY ""
set_kv "$I" ATLANTIA_SUPPORT_EMAIL soporte@atlantiasystems.com
set_kv "$I" ATLANTIA_SUPPORT_PHONE '"+502 4724-0606"'
set_kv "$I" ATLANTIA_SUPPORT_EMERGENCY_PHONE '"+502 4724-0606"'
set_kv "$I" ATLANTIA_SUPPORT_WHATSAPP +50247240606
set_kv "$I" ATLANTIA_POS_ENABLED false
set_kv "$I" ATLANTIA_POS_PROVIDER ""
set_kv "$I" ATLANTIA_POS_SUPPORT_PHONE ""
set_kv "$I" ATLANTIA_POS_MERCHANT_ID ""
set_kv "$I" ATLANTIA_POS_TERMINAL_IDS ""

# ---------------------------------------------------------------- ml.env
L="$SHARED_DIR/ml.env"
cp "$ROOT_DIR/ml-service/.env.production.example" "$L"
set_kv "$L" ML_SERVICE_TOKEN "$ML_SERVICE_TOKEN"
set_kv "$L" ML_WEBHOOK_SECRET "$ML_WEBHOOK_SECRET"
set_kv "$L" MARKETPLACE_BASE_URL "https://$DOMAIN"
set_kv "$L" MARKETPLACE_TOKEN "$MARKETPLACE_TOKEN"
set_kv "$L" REDIS_URL "redis://:$REDIS_PASSWORD@redis:6379/6"
set_kv "$L" CELERY_BROKER_URL "redis://:$REDIS_PASSWORD@redis:6379/7"
set_kv "$L" CELERY_RESULT_BACKEND "redis://:$REDIS_PASSWORD@redis:6379/8"
set_kv "$L" MLFLOW_TRACKING_URI file:///tmp/mlruns

# ---------------------------------------------------------------- servicios locales
cp "$ROOT_DIR/docker/env/mysql.env.example" "$SHARED_DIR/mysql.env"
set_kv "$SHARED_DIR/mysql.env" MYSQL_PASSWORD "$MYSQL_PASSWORD"
set_kv "$SHARED_DIR/mysql.env" MYSQL_ROOT_PASSWORD "$MYSQL_ROOT_PASSWORD"
cp "$ROOT_DIR/docker/env/redis.env.example" "$SHARED_DIR/redis.env"
set_kv "$SHARED_DIR/redis.env" REDIS_PASSWORD "$REDIS_PASSWORD"
cp "$ROOT_DIR/docker/env/search.env.example" "$SHARED_DIR/search.env"
set_kv "$SHARED_DIR/search.env" MEILI_MASTER_KEY "$MEILI_KEY"

chmod 600 "$SHARED_DIR"/*.env

echo
echo "Configuracion creada en $SHARED_DIR y $COMPOSE_ENV."
echo "Datos que faltan por llenar (preflight no deja desplegar mientras existan):"
grep -Hn 'CHANGE_ME' "$SHARED_DIR"/*.env | sed -E 's/=.*$/=.../' || true
echo
echo "Opcionales recomendados (dejar vacios si aun no los tienes): GOOGLE_MAPS_API_KEY en integrations.env,"
echo "RECAPTCHA_SITE_KEY/RECAPTCHA_SECRET_KEY en marketplace.env."
