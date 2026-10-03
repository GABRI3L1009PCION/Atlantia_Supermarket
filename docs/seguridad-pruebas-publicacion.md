# Seguridad, pruebas y publicación

## Seguridad cerrada

- El panel administrativo e interno sensible ahora obliga 2FA en login si el usuario tiene rol `super_admin`, `admin`, `empleado`, `soporte`, `contabilidad_finanzas` o `supervisor_logistica`.
- Los tokens Passport ya no quedan indefinidos: se controlan por variables de entorno y se purgan automáticamente todos los días.
- La app Android guarda sesión, UUID de instalación y preferencias operativas en almacenamiento cifrado.
- Producción queda parametrizada para rotación de secretos y para respaldos automáticos del MySQL.

## Variables nuevas

- `ATLANTIA_ENFORCE_2FA_ROLES`
- `ATLANTIA_ACCESS_TOKEN_TTL_MINUTES`
- `ATLANTIA_REFRESH_TOKEN_TTL_DAYS`
- `ATLANTIA_PERSONAL_ACCESS_TOKEN_TTL_DAYS`
- `ATLANTIA_BACKUP_DIR`
- `ATLANTIA_BACKUP_RETENTION_DAYS`
- `ATLANTIA_BACKUP_S3_PREFIX`

## Respaldo operativo

- Ejecutar `scripts/production/backup.sh` desde el host de producción.
- El script genera `database.sql.gz` y `manifest.json`.
- Si `aws` está instalado y `AWS_BUCKET` existe, también sube el respaldo a S3.
- Para archivos del sistema, la estrategia recomendada es mantener `PRIVATE_FILESYSTEM_DISK=s3` y versionado del bucket activo.

## Pruebas integrales recomendadas antes del piloto

### Cliente
- Registro, login, recuperación de contraseña y verificación de correo.
- Checkout con pago sandbox exitoso y rechazado.
- Seguimiento del pedido con cambios de estado reales.

### Vendedor
- Alta y edición de productos con imágenes en S3.
- Aprobación/rechazo de pedidos.
- Confirmación de inventario y tiempos de preparación.

### Administrador / backoffice
- Login con 2FA.
- Asignación manual y automática de repartidor.
- Revisión de retiros, liquidaciones, soporte y auditoría.

### Repartidor
- Login móvil.
- Registro de dispositivo FCM.
- GPS en foreground service.
- Flujo completo: aceptar, llegar, recoger, verificar código, entregar.
- Reconstrucción del estado al cerrar y reabrir la app.

### Resiliencia
- Redis apagado / reconexión.
- Pérdida de internet durante ruta.
- Dos pedidos simultáneos.
- Carga con colas, ofertas y notificaciones concurrentes.

## Pentest mínimo antes de producción

- `composer audit`
- Revisión OWASP ASVS nivel básico sobre auth, sesión, autorización y secretos.
- Validación manual de CORS, CSRF, rate limits, escalación horizontal y vertical.
- Revisión de almacenamiento local Android con `adb shell run-as`.
- Prueba de restauración de backup en staging.

## Publicación

### Staging
- Dominio staging con HTTPS.
- Base de datos y Redis propios.
- Firebase proyecto staging.
- APK signed de staging para piloto interno.

### Producción
- Ejecutar workflow CI.
- Ejecutar `scripts/production/preflight.sh`.
- Ejecutar `scripts/production/deploy.sh`.
- Confirmar `/health`, colas, scheduler, FCM y pagos sandbox.
- Hacer piloto controlado antes de abrir a todos.
