from app.domain.schemas.fraud import FraudDetectionRequest, FraudDetectionResponse


class FraudDetectionService:
    """Servicio antifraude basado en reglas y score interpretable."""

    def evaluate(self, request: FraudDetectionRequest) -> FraudDetectionResponse:
        """Evalua riesgo transaccional."""
        score = 0.05
        detalle: dict[str, float | int | str] = {}

        if request.total >= 1500:
            score += 0.3
            detalle["total_alto"] = request.total

        if request.intentos_pago >= 3:
            score += 0.25
            detalle["intentos_pago"] = request.intentos_pago

        item_count = sum(item.cantidad for item in request.items)
        if item_count >= 30:
            score += 0.2
            detalle["cantidad_items"] = item_count

        if request.metodo_pago == "tarjeta" and request.total >= 800:
            score += 0.1
            detalle["tarjeta_monto_alto"] = request.total

        score = round(min(score, 0.99), 6)
        tipo = "pedido_riesgo_alto" if score >= 0.75 else "pedido_riesgo_moderado" if score >= 0.45 else "normal"

        return FraudDetectionResponse(
            pedido_id=request.pedido_id,
            score_riesgo=score,
            tipo=tipo,
            detalle=detalle,
            requiere_revision=score >= 0.45,
        )
