import mlflow

from app.core.config import settings


class MlflowRegistry:
    """Adaptador de MLflow model registry."""

    def __init__(self) -> None:
        mlflow.set_tracking_uri(settings.mlflow_tracking_uri)

    def tracking_uri(self) -> str:
        """Devuelve tracking URI activo."""
        return settings.mlflow_tracking_uri

    def load_model_uri(self, name: str, stage: str = "Production") -> str:
        """Construye URI de modelo registrado."""
        return f"models:/{name}/{stage}"
