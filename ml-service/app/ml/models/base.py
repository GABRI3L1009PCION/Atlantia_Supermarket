from typing import Protocol


class PredictiveModel(Protocol):
    """Contrato minimo para modelos predictivos."""

    def predict(self, payload: dict) -> dict:
        """Ejecuta prediccion."""
        ...
