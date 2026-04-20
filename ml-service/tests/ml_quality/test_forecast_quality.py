"""Pruebas de calidad minima para forecast determinista."""

from datetime import date, timedelta

from app.domain.schemas.prediction import DemandPredictionRequest, SalesHistoryItem
from app.domain.services.prediction_service import DemandPredictionService


def test_forecast_stays_inside_reasonable_range_for_stable_sales() -> None:
    """Evita predicciones explosivas ante historial estable."""
    today = date.today()
    request = DemandPredictionRequest(
        producto_id=12,
        vendor_id=2,
        horizonte_dias=14,
        historial=[
            SalesHistoryItem(fecha=today - timedelta(days=day), unidades=10)
            for day in range(30, 0, -1)
        ],
    )

    response = DemandPredictionService().predict(request)
    forecasts = [point.valor_predicho for point in response.puntos]

    assert min(forecasts) >= 9.5
    assert max(forecasts) <= 11.5
    assert all(point.intervalo_inferior <= point.valor_predicho for point in response.puntos)
    assert all(point.intervalo_superior >= point.valor_predicho for point in response.puntos)
