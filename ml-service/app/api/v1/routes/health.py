from datetime import UTC, datetime

from fastapi import APIRouter

from app.core.config import settings
from app.domain.schemas.common import HealthResponse
from app.ml.registry.mlflow_registry import MlflowRegistry

router = APIRouter()


@router.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    """Verifica que el servicio responda."""
    return HealthResponse(
        status="ok",
        service=settings.app_name,
        version=settings.app_version,
        timestamp=datetime.now(UTC),
    )


@router.get("/ready", response_model=HealthResponse)
async def ready() -> HealthResponse:
    """Verifica dependencias principales sin bloquear arranque."""
    registry = MlflowRegistry()
    return HealthResponse(
        status="ready",
        service=settings.app_name,
        version=settings.app_version,
        timestamp=datetime.now(UTC),
        dependencies={
            "mlflow": registry.tracking_uri(),
            "redis": settings.redis_url,
        },
    )
