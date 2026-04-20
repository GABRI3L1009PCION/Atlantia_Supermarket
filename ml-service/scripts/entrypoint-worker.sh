#!/usr/bin/env sh
set -eu

python -m py_compile app/workers/tasks.py

exec celery -A app.workers.celery_app.celery_app worker --loglevel=INFO --concurrency="${CELERY_CONCURRENCY:-2}"
