import re

from app.domain.schemas.review import ReviewAnalysisRequest, ReviewAnalysisResponse


class ReviewAnalysisService:
    """Servicio NLP basico para resenas sospechosas."""

    spam_patterns = [
        r"(gratis|promocion|gana dinero|whatsapp)",
        r"(http://|https://|www\.)",
        r"(excelente){3,}",
    ]

    def analyze(self, request: ReviewAnalysisRequest) -> ReviewAnalysisResponse:
        """Calcula score de sospecha interpretable."""
        content = f"{request.titulo or ''} {request.contenido}".lower()
        score = 0.05

        if len(request.contenido.strip()) < 20:
            score += 0.2

        if request.calificacion in [1, 5] and len(request.contenido.strip()) < 40:
            score += 0.15

        for pattern in self.spam_patterns:
            if re.search(pattern, content):
                score += 0.3

        repeated_words = len(re.findall(r"\b(\w+)\b(?:\s+\1\b){2,}", content))
        score += min(repeated_words * 0.15, 0.3)
        score = round(min(score, 0.99), 6)
        reason = "patrones_spam_o_baja_calidad" if score >= 0.5 else "sin_patrones_sospechosos"

        return ReviewAnalysisResponse(
            resena_id=request.resena_id,
            score_sospecha=score,
            razon_ml=reason,
            flagged=score >= 0.5,
        )
