from datetime import UTC, datetime
from uuid import uuid4

from app.core.config import settings
from app.domain.schemas.training import (
    DriftMetricRequest,
    DriftMetricResponse,
    ModelRegistryItem,
    TrainingRequest,
    TrainingResponse,
)


class TrainingService:
    """Servicio de entrenamiento y registro de modelos."""

    def start_training(self, request: TrainingRequest) -> TrainingResponse:
        """Registra solicitud de entrenamiento manual."""
        return TrainingResponse(
            job_uuid=request.job_uuid or str(uuid4()),
            modelo_nombre=request.modelo_nombre,
            estado="queued",
            inicio_at=datetime.now(UTC),
        )

    def list_models(self) -> list[ModelRegistryItem]:
        """Devuelve modelos disponibles desde registry local inicial."""
        return [
            ModelRegistryItem(
                nombre_modelo="demand_forecast",
                version="fallback-1",
                estado="production",
                metricas={"mape": 0.0, "drift_score": 0.0},
            ),
            ModelRegistryItem(
                nombre_modelo="recommendations",
                version="fallback-1",
                estado="production",
                metricas={"precision_at_10": 0.0, "drift_score": 0.0},
            ),
        ]

    def register_drift(self, request: DriftMetricRequest) -> DriftMetricResponse:
        """Registra metrica drift y evalua umbral."""
        return DriftMetricResponse(
            modelo_nombre=request.modelo_nombre,
            version=request.version,
            drift_score=request.drift_score,
            drift_detectado=request.drift_score >= settings.drift_threshold,
        )
