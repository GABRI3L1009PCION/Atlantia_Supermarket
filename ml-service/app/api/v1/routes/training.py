from fastapi import APIRouter

from app.domain.schemas.training import DriftMetricRequest, DriftMetricResponse, TrainingRequest, TrainingResponse
from app.domain.services.training_service import TrainingService
from app.workers.tasks import enqueue_training

router = APIRouter()


@router.post("/start", response_model=TrainingResponse)
async def start_training(request: TrainingRequest) -> TrainingResponse:
    """Inicia entrenamiento manual y encola trabajo pesado."""
    response = TrainingService().start_training(request)
    enqueue_training.delay(response.job_uuid, response.modelo_nombre, request.parametros)
    return response


@router.post("/drift", response_model=DriftMetricResponse)
async def register_drift(request: DriftMetricRequest) -> DriftMetricResponse:
    """Registra metrica de drift enviada por jobs internos."""
    return TrainingService().register_drift(request)
