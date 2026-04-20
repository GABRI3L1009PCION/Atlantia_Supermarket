"""Pruebas de salud del microservicio ML."""

from fastapi.testclient import TestClient

from app.main import create_app


def test_health_endpoint_returns_ok() -> None:
    """Verifica que el endpoint de salud responda correctamente."""
    client = TestClient(create_app())

    response = client.get("/api/v1/health")

    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_readiness_endpoint_returns_ready() -> None:
    """Verifica que readiness este disponible para despliegues."""
    client = TestClient(create_app())

    response = client.get("/api/v1/ready")

    assert response.status_code == 200
    assert response.json()["status"] == "ready"
