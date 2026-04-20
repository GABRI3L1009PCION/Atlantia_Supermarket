from datetime import datetime

from pydantic import Field

from app.domain.schemas.common import BaseSchema


class TrainingRequest(BaseSchema):
    """Solicitud de entrenamiento manual."""

    job_uuid: str | None = None
    modelo_nombre: str = Field(min_length=3, max_length=140)
    parametros: dict[str, str | int | float | bool] = Field(default_factory=dict)


class TrainingResponse(BaseSchema):
    """Respuesta de entrenamiento."""

    job_uuid: str
    modelo_nombre: str
    estado: str
    inicio_at: datetime


class ModelRegistryItem(BaseSchema):
    """Modelo registrado."""

    nombre_modelo: str
    version: str
    estado: str
    metricas: dict[str, float] = Field(default_factory=dict)


class DriftMetricRequest(BaseSchema):
    """Solicitud para registrar drift."""

    modelo_nombre: str
    version: str
    mape: float | None = None
    rmse: float | None = None
    r2: float | None = None
    drift_score: float = Field(ge=0)


class DriftMetricResponse(BaseSchema):
    """Respuesta de metrica drift."""

    modelo_nombre: str
    version: str
    drift_score: float
    drift_detectado: bool
