from datetime import datetime
from typing import Any

from pydantic import BaseModel, ConfigDict, Field


class BaseSchema(BaseModel):
    """Base Pydantic con serializacion consistente."""

    model_config = ConfigDict(populate_by_name=True, from_attributes=True)


class HealthResponse(BaseSchema):
    """Respuesta de health/readiness."""

    status: str
    service: str
    version: str
    timestamp: datetime
    dependencies: dict[str, str] = Field(default_factory=dict)


class ErrorResponse(BaseSchema):
    """Respuesta uniforme de error."""

    message: str
    code: str
    errors: list[dict[str, Any]] | None = None
