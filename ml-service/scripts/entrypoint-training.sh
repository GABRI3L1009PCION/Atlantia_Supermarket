#!/usr/bin/env sh
set -eu

python -m py_compile app/workers/tasks.py

exec celery -A app.workers.celery_app.celery_app call ml.enqueue_training --args="${TRAINING_ARGS:-[]}"
