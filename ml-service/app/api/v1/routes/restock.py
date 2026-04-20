from fastapi import APIRouter

from app.domain.schemas.restock import RestockRequest, RestockResponse
from app.domain.services.restock_service import RestockSuggestionService

router = APIRouter()


@router.post("/suggest", response_model=RestockResponse)
async def suggest_restock(request: RestockRequest) -> RestockResponse:
    """Genera sugerencia de reabasto."""
    return RestockSuggestionService().suggest(request)
