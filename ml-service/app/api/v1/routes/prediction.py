from fastapi import APIRouter

from app.domain.schemas.prediction import DemandPredictionRequest, DemandPredictionResponse
from app.domain.services.prediction_service import DemandPredictionService

router = APIRouter()


@router.post("/demand", response_model=DemandPredictionResponse)
async def predict_demand(request: DemandPredictionRequest) -> DemandPredictionResponse:
    """Predice demanda de un producto."""
    return DemandPredictionService().predict(request)
