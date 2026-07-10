#!/usr/bin/env sh
set -eu

exec celery -A app.workers.celery_app.celery_app worker --loglevel=INFO --concurrency="${CELERY_CONCURRENCY:-2}"
