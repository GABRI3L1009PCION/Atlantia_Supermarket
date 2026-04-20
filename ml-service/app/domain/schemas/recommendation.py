from pydantic import Field

from app.domain.schemas.common import BaseSchema


class ProductFeature(BaseSchema):
    """Caracteristicas minimas de producto para recomendacion."""

    producto_id: int = Field(gt=0)
    categoria_id: int | None = None
    precio: float = Field(ge=0)
    nombre: str
    tags: list[str] = Field(default_factory=list)


class RecommendationRequest(BaseSchema):
    """Solicitud de recomendacion de productos."""

    cliente_id: int = Field(gt=0)
    productos: list[ProductFeature]
    historial_producto_ids: list[int] = Field(default_factory=list)
    limit: int = Field(default=12, ge=1, le=50)


class RecommendationItem(BaseSchema):
    """Producto recomendado."""

    producto_id: int
    score: float
    algoritmo: str
    posicion: int


class RecommendationResponse(BaseSchema):
    """Respuesta de recomendaciones."""

    cliente_id: int
    items: list[RecommendationItem]
