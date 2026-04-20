# Atlantia ML Service

Microservicio FastAPI para prediccion de demanda, recomendaciones, reabasto, antifraude,
analisis de resenas, entrenamiento manual, model registry y drift monitoring.

## Ejecutar local

```bash
uvicorn app.main:app --reload --host 0.0.0.0 --port 8001
```

## Seguridad

Las rutas bajo `/api/v1` requieren `Authorization: Bearer <ML_SERVICE_TOKEN>`.
Tambien aceptan HMAC con `X-ML-Signature: sha256=<firma>` para integraciones internas.
