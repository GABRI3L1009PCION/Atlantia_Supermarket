"""Aplicacion Celery del microservicio ML."""

from celery import Celery

from app.core.config import get_settings

settings = get_settings()

celery_app = Celery(
    "atlantia_ml",
    broker=settings.celery_broker_url,
    backend=settings.celery_result_backend,
    include=["app.workers.tasks"],
)

celery_app.conf.update(
    task_acks_late=True,
    task_reject_on_worker_lost=True,
    task_serializer="json",
    result_serializer="json",
    accept_content=["json"],
    timezone="America/Guatemala",
    enable_utc=True,
    worker_prefetch_multiplier=1,
)
