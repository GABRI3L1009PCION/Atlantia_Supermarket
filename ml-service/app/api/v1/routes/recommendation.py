from fastapi import APIRouter

from app.domain.schemas.recommendation import RecommendationRequest, RecommendationResponse
from app.domain.services.recommendation_service import ProductRecommendationService

router = APIRouter()


@router.post("/products", response_model=RecommendationResponse)
async def recommend_products(request: RecommendationRequest) -> RecommendationResponse:
    """Genera recomendaciones de productos."""
    return ProductRecommendationService().recommend(request)
