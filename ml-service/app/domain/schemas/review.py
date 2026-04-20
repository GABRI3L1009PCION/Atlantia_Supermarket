from pydantic import Field

from app.domain.schemas.common import BaseSchema


class ReviewAnalysisRequest(BaseSchema):
    """Solicitud de analisis NLP de resena."""

    resena_id: int = Field(gt=0)
    producto_id: int = Field(gt=0)
    cliente_id: int = Field(gt=0)
    calificacion: int = Field(ge=1, le=5)
    titulo: str | None = None
    contenido: str = Field(min_length=3, max_length=3000)


class ReviewAnalysisResponse(BaseSchema):
    """Respuesta de analisis de resena."""

    resena_id: int
    score_sospecha: float
    razon_ml: str
    flagged: bool
