# Observabilidad e incidentes de Atlantia

Esta guia deja el sistema listo para vigilar salud, responder a caidas y liberar cambios sin improvisacion.

## 1. Que queda monitoreado

- `/health` para chequeo externo rapido.
- Panel `admin/observabilidad` para salud extendida.
- Redis, MySQL, Meilisearch, ML, FCM, mapas, soporte y cobros presenciales.
- Jobs fallidos, heartbeat del scheduler, escritura de logs y antiguedad del ultimo respaldo.
- Comando `php artisan atlantia:ops-snapshot` para generar la fotografia operativa y disparar alertas controladas.

## 2. Variables nuevas

Agregar en el secreto real o `.env` del servidor:

```dotenv
ATLANTIA_LOG_STACK=daily,stderr,papertrail
ATLANTIA_INCIDENT_CHANNELS=slack,stderr
ATLANTIA_ALERTS_CHANNEL=incidents
ATLANTIA_ALERTS_COOLDOWN_MINUTES=15
ATLANTIA_QUEUE_FAILED_JOBS_WARNING=1
ATLANTIA_QUEUE_FAILED_JOBS_ERROR=10
ATLANTIA_SCHEDULER_HEARTBEAT_KEY=atlantia:ops:scheduler-heartbeat
ATLANTIA_SCHEDULER_STALE_AFTER_MINUTES=3
ATLANTIA_BACKUP_WARNING_HOURS=26
ATLANTIA_BACKUP_ERROR_HOURS=50
ATLANTIA_STAGING_URL=https://staging.atlantiasupermarket.com
ATLANTIA_STATUS_PAGE_URL=https://status.atlantiasupermarket.com
ATLANTIA_RUNBOOK_PATH=docs/observabilidad-incidentes.md
```

Si usaras Papertrail o Slack, completa tambien:

```dotenv
LOG_SLACK_WEBHOOK_URL=
PAPERTRAIL_URL=
PAPERTRAIL_PORT=
```

## 3. Flujo minimo ante incidente

1. Confirmar si `/health` esta en `ok` o `degraded`.
2. Abrir `admin/observabilidad` y localizar si el problema es base de datos, Redis, colas, scheduler o ML.
3. Revisar `failed_jobs`, estado del scheduler y la antiguedad del respaldo.
4. Aplicar mitigacion:
   - reiniciar workers
   - reintentar jobs solo si ya se corrigio la causa
   - activar modo mantenimiento si afecta checkout
   - hacer rollback si el fallo empezo tras release reciente
5. Registrar evidencia en auditoria y guardar hora de inicio, impacto, causa y accion tomada.

## 4. Runbook rapido

### Si cae checkout o pedidos

- Verificar `database`, `redis` y `queue`.
- Confirmar que `scheduler` siga vivo.
- Revisar logs centralizados del canal `operations`.

### Si no entran pedidos al repartidor

- Revisar `firebase_push`.
- Probar `atlantia:ops-snapshot`.
- Confirmar tokens FCM, workers `notifications` y conectividad Android.

### Si falla rastreo o mapas

- Revisar `maps_config`.
- Verificar cuotas, restricciones y llaves definitivas.

### Si hay riesgo de perdida de datos

- Validar ultimo respaldo y ejecutar uno nuevo.
- Confirmar restauracion de muestra antes de continuar cambios.

## 5. Recomendacion de operacion

- Correr el scheduler una sola vez en produccion.
- Mantener `atlantia:ops-snapshot` cada 5 minutos.
- Revisar el panel antes de cada despliegue.
- No liberar una version si hay errores activos en observabilidad.
