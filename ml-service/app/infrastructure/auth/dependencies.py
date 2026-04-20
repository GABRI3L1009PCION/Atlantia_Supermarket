import hashlib
import hmac

from fastapi import Header, HTTPException, Request, status

from app.core.config import settings


async def verify_marketplace_auth(
    request: Request,
    authorization: str | None = Header(default=None),
    x_ml_signature: str | None = Header(default=None),
) -> None:
    """Valida token bearer o firma HMAC enviada por Laravel."""
    if _valid_bearer(authorization):
        return

    body = await request.body()

    if _valid_signature(body, x_ml_signature):
        return

    raise HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail={"message": "Solicitud ML no autorizada.", "code": "ml_unauthorized"},
    )


def _valid_bearer(authorization: str | None) -> bool:
    if not settings.ml_service_token or not authorization:
        return False

    scheme, _, token = authorization.partition(" ")

    return scheme.lower() == "bearer" and hmac.compare_digest(token, settings.ml_service_token)


def _valid_signature(body: bytes, signature: str | None) -> bool:
    if not settings.ml_webhook_secret or not signature:
        return False

    expected = hmac.new(settings.ml_webhook_secret.encode(), body, hashlib.sha256).hexdigest()
    provided = signature.removeprefix("sha256=")

    return hmac.compare_digest(expected, provided)
