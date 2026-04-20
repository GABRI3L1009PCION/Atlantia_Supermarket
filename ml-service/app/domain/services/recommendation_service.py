from app.domain.schemas.recommendation import RecommendationItem, RecommendationRequest, RecommendationResponse


class ProductRecommendationService:
    """Servicio de recomendaciones hibridas con fallback por afinidad."""

    def recommend(self, request: RecommendationRequest) -> RecommendationResponse:
        """Ordena productos por afinidad simple y diversidad."""
        purchased = set(request.historial_producto_ids)
        scored: list[RecommendationItem] = []

        for product in request.productos:
            novelty = 0.15 if product.producto_id not in purchased else -0.35
            tag_score = min(len(product.tags) * 0.03, 0.15)
            price_score = max(0.0, 1 - (product.precio / 500)) * 0.2
            score = round(0.55 + novelty + tag_score + price_score, 6)
            scored.append(
                RecommendationItem(
                    producto_id=product.producto_id,
                    score=max(0.01, min(score, 0.99)),
                    algoritmo="hybrid_fallback",
                    posicion=0,
                )
            )

        items = sorted(scored, key=lambda item: item.score, reverse=True)[: request.limit]
        for index, item in enumerate(items, start=1):
            item.posicion = index

        return RecommendationResponse(cliente_id=request.cliente_id, items=items)
