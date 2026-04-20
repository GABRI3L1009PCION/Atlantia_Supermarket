"""Pruebas unitarias de sugerencia de reabasto."""

from app.domain.schemas.restock import RestockRequest
from app.domain.services.restock_service import RestockSuggestionService


def test_restock_suggestion_prioritizes_low_stock_product() -> None:
    """Recomienda reabasto urgente cuando la cobertura es insuficiente."""
    request = RestockRequest(
        producto_id=21,
        vendor_id=4,
        stock_actual=6,
        stock_minimo=10,
        ventas_promedio_diarias=5.0,
        lead_time_dias=3,
    )

    response = RestockSuggestionService().suggest(request)

    assert response.producto_id == 21
    assert response.stock_sugerido >= 15
    assert response.urgencia in {"alta", "critica"}
    assert response.dias_hasta_quiebre <= 2
