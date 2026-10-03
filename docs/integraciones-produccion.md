# Integraciones externas de produccion

El codigo ya contiene los adaptadores, variables y validaciones para INFILE, SMTP, S3, Firebase/FCM, mapas, soporte y terminales POS. Este documento indica que datos reales debe entregar cada proveedor antes del lanzamiento.

## Archivo unico de integraciones

En el servidor de produccion:

```bash
sudo install -d -m 700 /opt/atlantia/shared
sudo install -m 600 docker/env/integrations.env.example /opt/atlantia/shared/integrations.env
sudo nano /opt/atlantia/shared/integrations.env
```

Reemplaza todos los valores `CHANGE_ME`. El archivo se carga despues de `marketplace.env`, por lo que es la fuente definitiva para estas integraciones. Nunca debe subirse a Git, enviarse por chat ni incluirse en copias de soporte.

## INFILE

Solicita a INFILE:

- URL productiva de su API.
- Usuario y contrasena de integracion.
- Secreto independiente para validar webhooks.
- Credenciales, NIT, establecimiento, frases y escenarios FEL de cada vendedor emisor.

Configura `INFILE_MOCK=false`. El secreto del webhook debe tener al menos 32 caracteres aleatorios. Primero certifica una factura de prueba autorizada por INFILE y valida anulacion, reintento y descarga de XML/PDF.

## SMTP

Contrata un proveedor transaccional y obtiene host, puerto, usuario y contrasena de aplicacion. Configura una direccion del dominio empresarial y publica SPF, DKIM y DMARC. No uses la contrasena normal de un buzon personal.

El diagnostico comprueba configuracion y conectividad TCP, pero el piloto debe confirmar entrega real, rebotes y reputacion del dominio.

## S3

Crea un bucket privado con:

- Cifrado, versionado, bloqueo de acceso publico y reglas de ciclo de vida.
- Usuario IAM de minimo privilegio, limitado al bucket y prefijos requeridos.
- CORS solo para los dominios que realmente carguen archivos desde navegador.

Si el servidor usa un rol IAM, configura `AWS_USE_INSTANCE_PROFILE=true` y deja vacias las claves. En otro caso, ingresa una clave dedicada. Los documentos privados se sirven mediante rutas autorizadas, nunca como objetos publicos.

## Firebase y FCM

En Firebase:

1. Crea el proyecto definitivo.
2. Registra la app Android con su package ID productivo y huellas SHA-256.
3. Descarga `google-services.json` y colocalo en `app/google-services.json` del proyecto Android; no pertenece a este repositorio Laravel.
4. Crea una cuenta de servicio limitada para FCM y carga su correo y clave privada en `integrations.env`.

Prueba notificaciones con la app abierta, en segundo plano, cerrada y tras reiniciar el telefono. El token debe registrarse, rotarse y eliminarse al cerrar sesion.

## Mapas

El backend usa Mapbox para geocodificacion y rutas; Android/web usan Google Maps. Crea:

- Token publico Mapbox `pk.*`, restringido por aplicaciones o URL y con cuotas.
- API key Google Maps restringida por package ID/SHA-256 para Android y por dominio para web.
- Alertas de presupuesto y limites de consumo en ambos proveedores.

No reutilices una llave sin restricciones entre backend, web y Android.

## Soporte

Ingresa correo, telefono normal, telefono de emergencia y WhatsApp empresariales. Los canales de `ATLANTIA_SUPPORT_CHANNELS` deben corresponder a personal y horarios reales. Define responsables de guardia y confirma los SLA con el equipo antes de mantener los valores de la plantilla.

## Terminales POS

El banco o adquirente debe entregar:

- Nombre del proveedor.
- Merchant ID del comercio.
- Terminal ID de cada dispositivo.
- Telefono de soporte y procedimiento de contingencia.

Los IDs permiten conciliacion, pero no autorizan cobros por si solos. Cada repartidor debe estar asociado al terminal que transporta; el comprobante, monto, pedido y cierre del lote deben conciliarse diariamente.

Las transferencias quedan desactivadas con `ATLANTIA_TRANSFER_ENABLED=false` hasta disponer de una cuenta empresarial validada. El cobro en efectivo puede registrar la denominacion para la cual el cliente solicita cambio.

## Validacion

Validacion local de estructura, sin conexiones externas:

```bash
php artisan atlantia:integrations-readiness
```

Prueba segura de autenticacion o conectividad. No envia correos, no certifica facturas y no realiza cobros:

```bash
php artisan atlantia:integrations-readiness --probe
```

En produccion:

```bash
docker compose --env-file docker/env/compose.env -f docker-compose.prod.yml \
  run --rm --no-deps app php artisan atlantia:integrations-readiness --probe
```

El preflight de despliegue ejecuta automaticamente la validacion sin `--probe` y bloquea la publicacion si hay faltantes o valores inseguros.
