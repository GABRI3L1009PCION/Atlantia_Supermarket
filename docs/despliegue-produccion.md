# Despliegue de produccion de Atlantia

Esta guia deja el sistema publicado con HTTPS automatico, procesos reiniciables y dependencias privadas. Los secretos reales nunca se guardan en Git.

## 1. Preparar el servidor

- Linux actualizado con Docker Engine y Docker Compose. Ejecuta `sudo systemctl enable --now docker` para que los contenedores con `restart: unless-stopped` vuelvan automaticamente tras reiniciar el servidor.
- Usuario de despliegue sin acceso SSH por contrasena y con MFA en el proveedor.
- Puertos publicos permitidos: `80/tcp`, `443/tcp` y `443/udp`.
- MySQL, Redis, Meilisearch, PHP-FPM y ML no deben exponerse a Internet.
- El registro DNS `A` y, si aplica, `AAAA` del dominio debe apuntar al servidor.

Caddy obtiene y renueva automaticamente el certificado TLS. El dominio debe resolver al servidor antes de levantarlo por primera vez.

## 2. Crear archivos de configuracion

En el repositorio del servidor:

```bash
cp docker/env/compose.env.example docker/env/compose.env
sudo install -d -m 700 /opt/atlantia/shared
sudo install -m 600 .env.production.example /opt/atlantia/shared/marketplace.env
sudo install -m 600 ml-service/.env.production.example /opt/atlantia/shared/ml.env
```

Reemplaza todos los valores `CHANGE_ME`. Genera secretos independientes con:

```bash
openssl rand -base64 48
docker compose run --rm --no-deps app php artisan key:generate --show
```

Para el perfil local de contingencia, instala tambien `mysql.env`, `redis.env` y `search.env` desde las plantillas de `docker/env/`. No uses ese perfil como primera opcion para una operacion que necesite alta disponibilidad.

## 3. Servicios administrados

- **MySQL:** activa TLS, copias automaticas, recuperacion a un punto en el tiempo y una cuenta de aplicacion sin permisos administrativos.
- **Redis:** usa TLS y autenticacion, persistencia, limite de memoria y una red privada.
- **S3:** bucket privado, cifrado, versionado, bloqueo de acceso publico y ciclo de vida. Usa credenciales dedicadas o un rol de instancia con `AWS_USE_INSTANCE_PROFILE=true`.
- **SMTP:** usa una cuenta exclusiva, SPF, DKIM y DMARC. Para puerto 587 configura `MAIL_SCHEME=smtp`.
- **Meilisearch:** solo red privada, clave maestra larga, instantaneas y volumen persistente.

## 4. Publicar una version

Construye y publica `marketplace`, `marketplace-web`, `ml-api` y `ml-worker` con la misma etiqueta inmutable. Actualiza `APP_IMAGE_TAG` y ejecuta:

```bash
make prod-preflight
make prod-deploy
```

El despliegue valida secretos, descarga imagenes, ejecuta migraciones una sola vez, inicia servicios, reinicia workers y comprueba `https://DOMINIO/health`.

En el primer despliegue importa el catalogo existente una sola vez:

```bash
docker compose --env-file docker/env/compose.env -f docker-compose.prod.yml run --rm app php artisan scout:import "App\\Models\\Producto"
```

Antes de abrir al publico, realiza una carga y eliminacion de prueba en S3 y envia un correo SMTP a una cuenta controlada. Conserva la evidencia de ambas pruebas en la lista de salida a produccion.

## 5. Operacion continua

- Docker reinicia automaticamente Caddy, Nginx, Laravel, workers, scheduler y ML.
- Mantener exactamente una replica de `scheduler`.
- Alertar por HTTP 5xx, latencia, `/health` en 503, colas acumuladas, trabajos fallidos, disco, memoria Redis y conexiones MySQL.
- Probar restauraciones de MySQL y S3; una copia no probada no cuenta como recuperacion.
- Rotar credenciales de base de datos, Redis, SMTP, S3, Meilisearch, pagos, FEL y webhooks.
- Ejecutar `composer audit --locked` y `npm audit --audit-level=high` en cada build.

## 6. Datos que deben obtenerse fuera del codigo

El repositorio deja lista la configuracion, pero no puede inventar estos datos reales: dominio y DNS, cuenta del registro de imagenes, servidor MySQL/Redis, bucket S3, proveedor SMTP, claves de Meilisearch, Stripe, INFILE, mapas, reCAPTCHA y secretos de webhooks.
