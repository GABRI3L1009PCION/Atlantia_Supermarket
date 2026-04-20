from pydantic import Field

from app.domain.schemas.common import BaseSchema


class FraudOrderItem(BaseSchema):
    """Item de pedido evaluado por antifraude."""

    producto_id: int = Field(gt=0)
    cantidad: int = Field(gt=0)
    precio_unitario: float = Field(ge=0)


class FraudDetectionRequest(BaseSchema):
    """Solicitud de evaluacion antifraude."""

    pedido_id: int = Field(gt=0)
    cliente_id: int | None = None
    total: float = Field(ge=0)
    metodo_pago: str
    intentos_pago: int = Field(default=1, ge=0)
    items: list[FraudOrderItem]


class FraudDetectionResponse(BaseSchema):
    """Respuesta antifraude."""

    pedido_id: int
    score_riesgo: float
    tipo: str
    detalle: dict[str, float | int | str]
    requiere_revision: bool
