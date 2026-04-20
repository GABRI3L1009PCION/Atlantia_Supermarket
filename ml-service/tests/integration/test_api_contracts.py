"""Pruebas de contratos HTTP del microservicio ML."""

from datetime import date, timedelta

from fastapi.testclient import TestClient

from app.core.config import settings
from app.main import create_app


def test_prediction_endpoint_contract(monkeypatch) -> None:
    """Verifica contrato HTTP de prediccion bajo /api/v1."""
    token = "integration-token"
    settings.ml_service_token = token
    monkeypatch.setenv("ML_SERVICE_TOKEN", token)
    client = TestClient(create_app())
    today = date.today()

    response = client.post(
        "/api/v1/predict/demand",
        headers={"Authorization": f"Bearer {token}"},
        json={
            "producto_id": 30,
            "vendor_id": 6,
            "horizonte_dias": 5,
            "historial": [
                {"fecha": (today - timedelta(days=2)).isoformat(), "unidades": 7},
                {"fecha": (today - timedelta(days=1)).isoformat(), "unidades": 9},
            ],
        },
    )

    assert response.status_code == 200
    payload = response.json()
    assert payload["producto_id"] == 30
    assert len(payload["puntos"]) == 5


def test_model_registry_endpoint_requires_auth() -> None:
    """Verifica que el registro de modelos no sea publico."""
    client = TestClient(create_app())

    response = client.get("/api/v1/models")

    assert response.status_code == 401
