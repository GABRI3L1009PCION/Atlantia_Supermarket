from fastapi import APIRouter

from app.domain.schemas.review import ReviewAnalysisRequest, ReviewAnalysisResponse
from app.domain.services.review_service import ReviewAnalysisService

router = APIRouter()


@router.post("/analyze", response_model=ReviewAnalysisResponse)
async def analyze_review(request: ReviewAnalysisRequest) -> ReviewAnalysisResponse:
    """Analiza una resena con reglas NLP iniciales."""
    return ReviewAnalysisService().analyze(request)
