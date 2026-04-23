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

Para preparar el entorno:

```bash
cp .env.example .env
php artisan key:generate
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
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

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
