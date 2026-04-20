from functools import lru_cache

from pydantic import AnyHttpUrl, Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Configuracion central del microservicio ML."""

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    app_name: str = "Atlantia ML Service"
    app_version: str = "0.1.0"
    environment: str = Field(default="local", alias="APP_ENV")
    debug: bool = Field(default=False, alias="APP_DEBUG")

    ml_service_token: str = Field(default="", alias="ML_SERVICE_TOKEN")
    ml_webhook_secret: str = Field(default="", alias="ML_WEBHOOK_SECRET")

    marketplace_base_url: AnyHttpUrl | None = Field(default=None, alias="MARKETPLACE_BASE_URL")
    marketplace_token: str = Field(default="", alias="MARKETPLACE_TOKEN")

    redis_url: str = Field(default="redis://localhost:6379/0", alias="REDIS_URL")
    celery_broker_url: str = Field(default="redis://localhost:6379/1", alias="CELERY_BROKER_URL")
    celery_result_backend: str = Field(default="redis://localhost:6379/2", alias="CELERY_RESULT_BACKEND")

    mlflow_tracking_uri: str = Field(default="file:./mlruns", alias="MLFLOW_TRACKING_URI")
    drift_threshold: float = Field(default=0.25, alias="DRIFT_THRESHOLD")


@lru_cache
def get_settings() -> Settings:
    """Devuelve configuracion cacheada."""
    return Settings()


settings = get_settings()
