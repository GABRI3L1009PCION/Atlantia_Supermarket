#!/usr/bin/env sh
set -eu

exec celery -A app.workers.celery_app.celery_app call ml.enqueue_training --args="${TRAINING_ARGS:-[]}"
