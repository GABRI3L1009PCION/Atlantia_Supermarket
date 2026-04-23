# Atlantia Supermarket

Marketplace multirrol para supermercado online en Izabal, Guatemala.

## Stack

- Laravel 13 y PHP 8.3+
- Livewire 3
- Tailwind CSS 4
- MySQL 8
- Redis y colas Laravel
- Laravel Passport
- Spatie Permission
- Meilisearch
- Mapbox
- Microservicio ML propio en FastAPI

## Configuracion segura del entorno

Nunca subas `.env` al repositorio. El archivo ya esta ignorado por Git y debe existir solo en cada servidor o maquina local.

Para preparar el entorno, copia la plantilla:

```bash
cp .env.example .env
```

Luego llena los valores reales en `.env`. Usa nombres de secretos dedicados:

- `ATLANTIA_DB_PASSWORD` para la contrasena de base de datos.
- `ATLANTIA_MAIL_APP_PASSWORD` para la contrasena de aplicacion del correo SMTP.
- `ATLANTIA_MAPBOX_TOKEN` para el token publico de Mapbox.

Para rendimiento, sesiones, cache y colas usan Redis:

- `SESSION_DRIVER=redis`
- `SESSION_CONNECTION=session`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `REDIS_CLIENT=predis`
- `REDIS_HOST=127.0.0.1`

En produccion:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE=true`
- `LOG_LEVEL=warning`

La aplicacion bloquea el arranque si `APP_ENV=production` y `APP_DEBUG=true` estan activos al mismo tiempo.

## Instalacion local

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan scout:import "App\Models\Producto"
npm run build
php artisan serve
```

Tambien puedes usar el comando automatizado:

```bash
make setup-local
```

Este objetivo ejecuta:

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan scout:import "App\Models\Producto"
npm run build
```

## Health check

El endpoint operativo del marketplace queda disponible en:

```bash
GET /health
```

Respuesta esperada:

```json
{
  "status": "ok",
  "database": "ok",
  "redis": "ok",
  "meilisearch": "ok",
  "ml_service": "ok",
  "timestamp": "2026-04-23T00:00:00-06:00"
}
```

Si algun servicio falla, responde `503` con el detalle del servicio afectado. Esto sirve para Docker healthchecks, balanceadores y monitoreo externo.

## Seguridad operativa

- Los webhooks no usan CSRF porque no dependen de sesion, pero deben ir protegidos por HMAC.
- Las sesiones se cifran por defecto.
- El CSP usa nonce por request y no permite `unsafe-inline` ni `unsafe-eval`.
- Los headers de seguridad se aplican en respuestas web y API.

## Desarrollo

Antes de enviar cambios:

```bash
php artisan test
npm run build
```
