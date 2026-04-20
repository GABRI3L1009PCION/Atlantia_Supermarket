"""Tareas asincronas para entrenamiento, drift y exportacion de datasets."""

from __future__ import annotations

import logging
from datetime import datetime, timezone
from typing import Any

from app.domain.schemas.training import TrainingRequest, TrainingResponse
from app.domain.services.training_service import TrainingService
from app.workers.celery_app import celery_app

logger = logging.getLogger(__name__)


@celery_app.task(name="ml.enqueue_training", bind=True, autoretry_for=(Exception,), retry_backoff=True, max_retries=3)
def enqueue_training(self: Any, payload: dict[str, Any]) -> dict[str, Any]:
    """Registra una solicitud de entrenamiento para ejecucion asincrona."""
    request = TrainingRequest(**payload)
    service = TrainingService()
    response: TrainingResponse = service.start_training(request)

    logger.info(
        "training_job_queued",
        extra={
            "job_id": response.job_id,
            "modelo_nombre": response.modelo_nombre,
            "requested_at": datetime.now(timezone.utc).isoformat(),
        },
    )

    return response.model_dump(mode="json")
