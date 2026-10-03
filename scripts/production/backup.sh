#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SHARED_ENV="${ATLANTIA_SHARED_ENV:-/opt/atlantia/shared/marketplace.env}"

fail() {
    echo "Backup failed: $1" >&2
    exit 1
}

[ -f "$SHARED_ENV" ] || fail "Shared environment file not found: $SHARED_ENV"

set -a
source "$SHARED_ENV"
set +a

: "${DB_HOST:?DB_HOST is required}"
: "${DB_PORT:=3306}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"

DB_PASSWORD_VALUE="${ATLANTIA_DB_PASSWORD:-${DB_PASSWORD:-}}"
[ -n "$DB_PASSWORD_VALUE" ] || fail "ATLANTIA_DB_PASSWORD or DB_PASSWORD is required"

BACKUP_DIR="${ATLANTIA_BACKUP_DIR:-$ROOT_DIR/storage/app/backups}"
RETENTION_DAYS="${ATLANTIA_BACKUP_RETENTION_DAYS:-14}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
TARGET_DIR="$BACKUP_DIR/$TIMESTAMP"
mkdir -p "$TARGET_DIR"

export MYSQL_PWD="$DB_PASSWORD_VALUE"

mysqldump \
    --single-transaction \
    --quick \
    --set-gtid-purged=OFF \
    --ssl-mode=REQUIRED \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    -u "$DB_USERNAME" \
    "$DB_DATABASE" | gzip -9 > "$TARGET_DIR/database.sql.gz"

cat > "$TARGET_DIR/manifest.json" <<EOF
{
  "created_at_utc": "$TIMESTAMP",
  "database": "$DB_DATABASE",
  "host": "$DB_HOST",
  "retention_days": $RETENTION_DAYS
}
EOF

if command -v aws >/dev/null 2>&1 && [ -n "${AWS_BUCKET:-}" ]; then
    prefix="${ATLANTIA_BACKUP_S3_PREFIX:-backups/marketplace}"
    aws s3 cp "$TARGET_DIR/database.sql.gz" "s3://${AWS_BUCKET}/${prefix}/${TIMESTAMP}/database.sql.gz"
    aws s3 cp "$TARGET_DIR/manifest.json" "s3://${AWS_BUCKET}/${prefix}/${TIMESTAMP}/manifest.json"
fi

find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +"$RETENTION_DAYS" -exec rm -rf {} +

echo "Backup completed successfully at $TARGET_DIR"
