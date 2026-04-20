from fastapi import APIRouter, Depends

from app.api.v1.routes import fraud, health, models, prediction, recommendation, restock, review, training
from app.infrastructure.auth.dependencies import verify_marketplace_auth

api_router = APIRouter()

api_router.include_router(health.router, tags=["health"])

protected_dependencies = [Depends(verify_marketplace_auth)]
api_router.include_router(
    prediction.router,
    prefix="/predict",
    tags=["prediction"],
    dependencies=protected_dependencies,
)
api_router.include_router(
    recommendation.router,
    prefix="/recommend",
    tags=["recommendation"],
    dependencies=protected_dependencies,
)
api_router.include_router(restock.router, prefix="/restock", tags=["restock"], dependencies=protected_dependencies)
api_router.include_router(fraud.router, prefix="/fraud", tags=["fraud"], dependencies=protected_dependencies)
api_router.include_router(review.router, prefix="/reviews", tags=["reviews"], dependencies=protected_dependencies)
api_router.include_router(training.router, prefix="/training", tags=["training"], dependencies=protected_dependencies)
api_router.include_router(models.router, prefix="/models", tags=["models"], dependencies=protected_dependencies)
