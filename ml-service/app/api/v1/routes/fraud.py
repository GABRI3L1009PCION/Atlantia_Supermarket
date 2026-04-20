from fastapi import APIRouter

from app.domain.schemas.fraud import FraudDetectionRequest, FraudDetectionResponse
from app.domain.services.fraud_service import FraudDetectionService

router = APIRouter()


@router.post("/orders", response_model=FraudDetectionResponse)
async def detect_order_fraud(request: FraudDetectionRequest) -> FraudDetectionResponse:
    """Evalua riesgo antifraude de un pedido."""
    return FraudDetectionService().evaluate(request)
