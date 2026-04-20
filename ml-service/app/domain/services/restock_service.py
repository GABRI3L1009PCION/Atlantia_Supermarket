import math

from app.domain.schemas.restock import RestockRequest, RestockResponse


class RestockSuggestionService:
    """Servicio de sugerencias de reabasto."""

    def suggest(self, request: RestockRequest) -> RestockResponse:
        """Calcula punto de reorden y urgencia."""
        if request.ventas_promedio_diarias <= 0:
            return RestockResponse(
                producto_id=request.producto_id,
                vendor_id=request.vendor_id,
                stock_sugerido=max(request.stock_minimo, request.stock_actual),
                dias_hasta_quiebre=None,
                urgencia="baja",
                razon="No hay ventas recientes suficientes para sugerir aumento.",
            )

        dias_hasta_quiebre = math.floor(request.stock_actual / request.ventas_promedio_diarias)
        stock_seguridad = math.ceil(request.ventas_promedio_diarias * 2)
        stock_sugerido = math.ceil((request.ventas_promedio_diarias * request.lead_time_dias) + stock_seguridad)

        urgencia = "baja"
        if dias_hasta_quiebre <= request.lead_time_dias:
            urgencia = "critica"
        elif dias_hasta_quiebre <= request.lead_time_dias + 3:
            urgencia = "alta"
        elif request.stock_actual <= request.stock_minimo:
            urgencia = "media"

        return RestockResponse(
            producto_id=request.producto_id,
            vendor_id=request.vendor_id,
            stock_sugerido=max(stock_sugerido, request.stock_minimo),
            dias_hasta_quiebre=dias_hasta_quiebre,
            urgencia=urgencia,
            razon="Calculo basado en ventas promedio, lead time y stock de seguridad.",
        )
