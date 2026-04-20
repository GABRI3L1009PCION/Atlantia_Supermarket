from fastapi import APIRouter

from app.domain.schemas.training import ModelRegistryItem
from app.domain.services.training_service import TrainingService

router = APIRouter()


@router.get("", response_model=list[ModelRegistryItem])
async def list_models() -> list[ModelRegistryItem]:
    """Lista modelos registrados disponibles."""
    return TrainingService().list_models()
