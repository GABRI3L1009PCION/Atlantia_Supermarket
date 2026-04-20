"""Pruebas del endpoint de prediccion de demanda."""

from __future__ import annotations

from datetime import date, timedelta

from fastapi.testclient import TestClient

from app.core.config import settings
from app.main import create_app


def test_demand_prediction_requires_auth() -> None:
    """Verifica que la prediccion exija autenticacion interna."""
    client = TestClient(create_app())

    response = client.post("/api/v1/predict/demand", json={})

    assert response.status_code == 401


def test_demand_prediction_returns_forecast(monkeypatch) -> None:
    """Verifica que la prediccion devuelva horizonte solicitado."""
    token = "test-ml-token"
    monkeypatch.setenv("ML_SERVICE_TOKEN", token)
    settings.ml_service_token = token
    client = TestClient(create_app())
    today = date.today()

    payload = {
        "producto_id": 15,
        "vendor_id": 3,
        "horizonte_dias": 7,
        "historial": [
            {"fecha": (today - timedelta(days=3)).isoformat(), "unidades": 12},
            {"fecha": (today - timedelta(days=2)).isoformat(), "unidades": 16},
            {"fecha": (today - timedelta(days=1)).isoformat(), "unidades": 14},
        ],
    }

    response = client.post(
        "/api/v1/predict/demand",
        headers={"Authorization": f"Bearer {token}"},
        json=payload,
    )

    assert response.status_code == 200
    body = response.json()
    assert body["producto_id"] == 15
    assert len(body["puntos"]) == 7
    assert body["modelo"] == "fallback_media_movil"
