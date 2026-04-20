"""Pruebas unitarias de analisis de reviews."""

from app.domain.schemas.review import ReviewAnalysisRequest
from app.domain.services.review_service import ReviewAnalysisService


def test_review_analysis_flags_spam_patterns() -> None:
    """Marca como sospechosa una review con patrones de spam."""
    request = ReviewAnalysisRequest(
        resena_id=45,
        producto_id=8,
        cliente_id=3,
        calificacion=5,
        contenido="Excelente excelente excelente comprar ahora gratis gratis gratis",
    )

    response = ReviewAnalysisService().analyze(request)

    assert response.resena_id == 45
    assert response.flagged is True
    assert response.score_sospecha >= 0.65
    assert "spam" in response.razon_ml
