from datetime import date, timedelta

import numpy as np

from app.domain.schemas.prediction import DemandPredictionPoint, DemandPredictionRequest, DemandPredictionResponse


class DemandPredictionService:
    """Servicio de prediccion de demanda con fallback estadistico."""

    def predict(self, request: DemandPredictionRequest) -> DemandPredictionResponse:
        """Genera prediccion determinista basada en historial."""
        values = [item.unidades for item in request.historial]
        base = float(np.mean(values[-14:])) if values else 1.0
        volatility = float(np.std(values[-30:])) if len(values) > 1 else max(base * 0.15, 1.0)
        last_date = request.historial[-1].fecha if request.historial else None

        puntos: list[DemandPredictionPoint] = []
        for index in range(1, request.horizonte_dias + 1):
            fecha = (last_date or date.today()) + timedelta(days=index)
            seasonal = 1 + (0.08 if fecha.weekday() in [5, 6] else 0)
            forecast = max(0.0, base * seasonal)
            puntos.append(
                DemandPredictionPoint(
                    fecha=fecha,
                    valor_predicho=round(forecast, 2),
                    intervalo_inferior=round(max(0.0, forecast - volatility), 2),
                    intervalo_superior=round(forecast + volatility, 2),
                )
            )

        return DemandPredictionResponse(
            producto_id=request.producto_id,
            vendor_id=request.vendor_id,
            horizonte_dias=request.horizonte_dias,
            modelo="fallback_media_movil",
            puntos=puntos,
        )
