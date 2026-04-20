from datetime import date

from pydantic import Field

from app.domain.schemas.common import BaseSchema


class SalesHistoryItem(BaseSchema):
    """Punto historico de ventas."""

    fecha: date
    unidades: float = Field(ge=0)


class DemandPredictionRequest(BaseSchema):
    """Solicitud de prediccion de demanda."""

    producto_id: int = Field(gt=0)
    vendor_id: int = Field(gt=0)
    horizonte_dias: int = Field(default=14, ge=1, le=90)
    historial: list[SalesHistoryItem] = Field(default_factory=list)


class DemandPredictionPoint(BaseSchema):
    """Punto pronosticado."""

    fecha: date
    valor_predicho: float
    intervalo_inferior: float
    intervalo_superior: float


class DemandPredictionResponse(BaseSchema):
    """Respuesta de prediccion."""

    producto_id: int
    vendor_id: int
    horizonte_dias: int
    modelo: str
    puntos: list[DemandPredictionPoint]
