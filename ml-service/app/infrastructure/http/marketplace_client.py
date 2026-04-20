import httpx

from app.core.config import settings


class MarketplaceClient:
    """Cliente HTTP seguro hacia el marketplace Laravel."""

    async def post(self, path: str, payload: dict) -> dict:
        """Envia payload al marketplace."""
        if settings.marketplace_base_url is None:
            return {"delivered": False, "reason": "marketplace_base_url_not_configured"}

        headers = {"Authorization": f"Bearer {settings.marketplace_token}"}

        async with httpx.AsyncClient(base_url=str(settings.marketplace_base_url), timeout=20) as client:
            response = await client.post(path, json=payload, headers=headers)
            response.raise_for_status()
            return response.json()
