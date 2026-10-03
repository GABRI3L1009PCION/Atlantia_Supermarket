# Staging local reproducible

Este entorno levanta una copia aislada del sistema con:

- Laravel/PHP 8.3 y Nginx en `http://127.0.0.1:8180`.
- MySQL 8.4 en `127.0.0.1:13306`.
- Redis 7.2 en `127.0.0.1:16379`.
- Meilisearch 1.12 en `http://127.0.0.1:17700`.
- API ML en `http://127.0.0.1:18000`.
- Worker Laravel, scheduler Laravel y worker Celery.

Los puertos publicados estan enlazados a `127.0.0.1`; el entorno no queda
expuesto a la red local. `verify` comprueba HTTP, migraciones, dependencias
operativas de Laravel y que el worker Celery responda.

## Operacion

```powershell
.\scripts\staging\staging.ps1 up
.\scripts\staging\staging.ps1 verify
.\scripts\staging\staging.ps1 status
.\scripts\staging\staging.ps1 logs
.\scripts\staging\staging.ps1 down
```

La primera ejecucion crea `.env.staging.docker.local` y
`ml-service/.env.staging.docker.local` con secretos aleatorios. Ambos archivos
estan ignorados por Git. Los datos se conservan en volumenes Docker aunque se
ejecute `down`.

Para eliminar deliberadamente todos los datos de staging:

```powershell
docker compose --env-file .env.staging.docker.local -f docker-compose.staging.yml down -v
```
