"""Pruebas de autenticacion interna del microservicio."""

import hashlib
import hmac

from fastapi.testclient import TestClient

from app.core.config import settings
from app.main import create_app


def test_hmac_signature_allows_internal_request(monkeypatch) -> None:
    """Verifica autenticacion HMAC para integraciones sin bearer token."""
    secret = "local-webhook-secret"
    monkeypatch.setenv("ML_WEBHOOK_SECRET", secret)
    settings.ml_webhook_secret = secret
    client = TestClient(create_app())
    payload = b'{"pedido_id":11,"cliente_id":8,"total":425.50,"items":[],"metodo_pago":"tarjeta"}'
    signature = hmac.new(secret.encode("utf-8"), payload, hashlib.sha256).hexdigest()

    response = client.post(
        "/api/v1/fraud/orders",
        headers={"X-ML-Signature": signature, "Content-Type": "application/json"},
        content=payload,
    )

    assert response.status_code == 200
    assert "score_riesgo" in response.json()
