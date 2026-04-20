from pydantic import Field

from app.domain.schemas.common import BaseSchema


class RestockRequest(BaseSchema):
    """Solicitud de sugerencia de reabasto."""

    producto_id: int = Field(gt=0)
    vendor_id: int = Field(gt=0)
    stock_actual: int = Field(ge=0)
    stock_minimo: int = Field(ge=0)
    ventas_promedio_diarias: float = Field(ge=0)
    lead_time_dias: int = Field(default=3, ge=1, le=60)


class RestockResponse(BaseSchema):
    """Respuesta de sugerencia de reabasto."""

    producto_id: int
    vendor_id: int
    stock_sugerido: int
    dias_hasta_quiebre: int | None
    urgencia: str
    razon: str
