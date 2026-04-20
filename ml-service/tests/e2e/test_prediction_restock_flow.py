"""Pruebas E2E del flujo prediccion a reabasto."""

from datetime import date, timedelta

from fastapi.testclient import TestClient

from app.core.config import settings
from app.main import create_app


def test_prediction_result_can_drive_restock_suggestion(monkeypatch) -> None:
    """Usa prediccion de demanda como entrada para reabasto."""
    token = "e2e-token"
    settings.ml_service_token = token
    monkeypatch.setenv("ML_SERVICE_TOKEN", token)
    client = TestClient(create_app())
    today = date.today()
    headers = {"Authorization": f"Bearer {token}"}

    prediction = client.post(
        "/api/v1/predict/demand",
        headers=headers,
        json={
            "producto_id": 77,
            "vendor_id": 9,
            "horizonte_dias": 7,
            "historial": [
                {"fecha": (today - timedelta(days=3)).isoformat(), "unidades": 11},
                {"fecha": (today - timedelta(days=2)).isoformat(), "unidades": 12},
                {"fecha": (today - timedelta(days=1)).isoformat(), "unidades": 13},
            ],
        },
    )
    assert prediction.status_code == 200
    promedio = sum(point["valor_predicho"] for point in prediction.json()["puntos"]) / 7

    restock = client.post(
        "/api/v1/restock/suggest",
        headers=headers,
        json={
            "producto_id": 77,
            "vendor_id": 9,
            "stock_actual": 10,
            "stock_minimo": 8,
            "ventas_promedio_diarias": promedio,
            "lead_time_dias": 3,
        },
    )

    assert restock.status_code == 200
    body = restock.json()
    assert body["producto_id"] == 77
    assert body["stock_sugerido"] > 10
    assert body["urgencia"] in {"media", "alta", "critica"}
