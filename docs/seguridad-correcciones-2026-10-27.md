# Correcciones de seguridad — 27 de octubre de 2026

Se corrigieron los hallazgos de dependencias y los problemas confirmados de integridad financiera y transporte. Estos resultados no equivalen a una garantía de seguridad del 100 % ni a la aceptación de un despliegue de producción.

## Resultado verificable

| Comprobación | Resultado |
|---|---|
| Composer audit, lockfile actualizado | **0 avisos conocidos**, 0 paquetes abandonados |
| npm audit, aplicación web | **0 avisos conocidos**, incluyendo dependencias de desarrollo |
| npm audit, aplicación de repartidores | **0 avisos conocidos**, incluyendo dependencias de desarrollo |
| PHPUnit | **162 pruebas correctas, 553 aserciones**, SQLite en memoria |
| Formato PHP de los archivos modificados | Pint correcto |
| Compilación web | Correcta, Vite 8.3.5 |
| Composer validate --strict | Correcto |
| JavaScript de la app móvil | Sintaxis válida con `node --check` |
| Validación de transporte móvil | **14 pruebas correctas** en Node.js |
| Sincronización Capacitor Android | Correcta |
| Compilación Android y pruebas instrumentadas | **Pendientes**: no hay SDK configurado en esta PC |

Las evidencias se guardaron en [la carpeta privada de resultados](C:/laragon/www/Atlantia_Supermarket/storage/app/private/seguridad-2026-10-27). Las auditorías consultaron los registros de paquetes; no se realizaron cobros ni se enviaron correos, y no se modificó la base MySQL del proyecto.

## Dependencias

Se actualizaron los lockfiles sin migrar a nuevas versiones mayores de Laravel, Livewire o Capacitor. Entre las versiones instaladas están Laravel 13.39.0, Livewire 3.8.11, Guzzle 7.16.1 y CommonMark 2.10.4. Las dependencias indirectas necesarias también se resolvieron e instalaron.

El ZIP original había conservado la información de instalación desde Git, pero excluido sus directorios `.git`. Composer no podía actualizar esos paquetes en su lugar. Se preservó la carpeta anterior como respaldo privado y se reinstaló `vendor` desde el lockfile actualizado. El código activo utiliza las dependencias nuevas; el respaldo no es parte del autoload ni del directorio público.

## Integridad de pagos y devoluciones

- Un reembolso debe ser finito, positivo y no superar el importe acreditado ni el total del pedido.
- Un pago pendiente, anulado o ya reembolsado no puede volver a reembolsarse.
- Se bloquean las filas de pago y pedido durante la resolución para evitar aprobaciones simultáneas incompatibles.
- Se rechaza una segunda devolución aprobada del mismo pedido y una aprobación sin pago acreditado asociado.
- El caso de Q1,000 sobre un pago de Q28 devuelve error de validación y conserva el pedido, el pago y la devolución sin cambios.
- Se registra el importe y la fecha del reembolso en el payload del pago.
- Un webhook antiguo no vuelve a aprobar un pago anulado/reembolsado. `processing` y `requires_capture` quedan en validación, no se consideran dinero cobrado.

Se conserva el modelo existente de una devolución aprobada por pedido/pago. No se añadió un sistema de múltiples reembolsos parciales ni una devolución de dinero automática para efectivo/POS. Las operaciones manuales siguen necesitando conciliación y comprobantes. Si un pedido hijo no tiene un pago acreditado asociado, su aprobación se rechaza; no se inventa ni simula el movimiento del pago padre. La conciliación de devoluciones por vendedor contra pagos agrupados requiere su flujo específico antes de habilitar esa operación.

## Webhooks y trabajos en segundo plano

Se conserva la validación HMAC, timestamp y evento. Ahora existe un bloqueo por evento mientras se procesa. Solo una respuesta satisfactoria lo marca como procesado: un error 500 o una excepción libera el bloqueo y permite un reintento válido. Los eventos simultáneos o repetidos tras un éxito se rechazan.

`REDIS_QUEUE_RETRY_AFTER` pasa a 240 segundos, superior a los timeouts de 120/180 segundos de los workers incluidos. Un servidor con un valor propio más bajo debe actualizarlo también; cambiar el valor por defecto del repositorio no modifica secretos ni variables externas.

## HTTPS, orígenes y sesiones

- Se eliminó el bypass de HTTPS basado en leer directamente `X-Forwarded-Proto`. Se usa el resultado de la validación de proxies de Laravel/Symfony.
- En producción se comprueba que el host de la solicitud coincida con el host de `APP_URL`, evitando generar enlaces sensibles desde un host arbitrario.
- La excepción que refleja orígenes y permite Private Network Access solo funciona en `APP_ENV=local`.
- Los preflights de producción pasan por la lista CORS configurada; la ruta OPTIONS ya no devuelve permisos universales por su cuenta.
- Las respuestas de API y páginas autenticadas llevan `no-store`.
- El arranque de producción rechaza sesiones sin cifrado, cookies sin `Secure`/`HttpOnly`, `APP_DEBUG=true`, `APP_URL` sin HTTPS, proxies globalmente confiados y orígenes CORS vacíos, inseguros o comodín.

La configuración final debe declarar el dominio real y únicamente sus orígenes autorizados. Ejemplo:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio-real.example
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
CORS_ALLOWED_ORIGINS=https://tu-dominio-real.example,https://localhost,capacitor://localhost
REDIS_QUEUE_RETRY_AFTER=240
```

El dominio de ejemplo debe sustituirse por el real. `https://localhost` y `capacitor://localhost` son orígenes de la interfaz instalada, no una autorización general para otros sitios. Los valores de `TRUSTED_PROXIES` deben corresponder al proxy real; no se debe usar `*`.

Después de actualizar la configuración en el servidor, hay que regenerar su caché, reiniciar los workers y probar con la terminación TLS y los proxies reales. No se modificaron aquí las credenciales del servidor ni el `.env` de desarrollo.

## Android

- El manifiesto principal impide HTTP. Solo la variante debug permite tráfico sin cifrar para pruebas locales.
- Capacitor usa HTTPS por defecto. La excepción local requiere `ATLANTIA_MOBILE_DEBUG_HTTP=true` antes de sincronizar.
- El servicio GPS guarda el token como AES-GCM con clave en Android Keystore. No se guarda la clave en preferencias.
- El token antiguo en texto claro se elimina y requiere iniciar sesión de nuevo. Un ciphertext inválido o un error criptográfico detiene el uso del token.
- Al detener el seguimiento se elimina el token persistido.
- El plugin valida el esquema y el host de la URL, y rechaza HTTP cuando la política de red lo prohíbe.
- La interfaz usa HTTPS por defecto, rechaza URL con credenciales/parámetros y no escanea servidores HTTP en producción. Si el usuario elige un servidor, no envía las credenciales a otro servidor de respaldo. Las peticiones de login/API no siguen redirecciones ni se almacenan en caché.
- Una compilación release no puede empaquetarse sin los datos de firma suministrados por variables de entorno. No se generaron claves privadas de publicación ni se incluyeron secretos en Git.

Para publicar se necesitan las variables `ATLANTIA_ANDROID_KEYSTORE`, `ATLANTIA_ANDROID_STORE_PASSWORD`, `ATLANTIA_ANDROID_KEY_ALIAS` y `ATLANTIA_ANDROID_KEY_PASSWORD`, con una clave de firma propiedad del responsable de la aplicación. Se requieren también el SDK/JDK compatibles y la configuración real de Firebase para push.

Se añadieron tres pruebas instrumentadas que ejercitan el Keystore real: cifrado/lectura, eliminación del token legado y rechazo de datos manipulados. **No se ejecutaron ni se compiló el APK en esta PC**; la ruta del SDK guardada pertenecía a la otra máquina y no existe aquí. Antes de distribuir hay que ejecutar esas pruebas, compilar y probar GPS, bloqueo de pantalla, reconexión y cierre de sesión en un dispositivo.

## Controles del repositorio

Se agregaron un workflow de auditoría de Composer/npm y configuración de Dependabot. Revisan las dependencias PHP, web y móvil y los workflows. La auditoría contempla pull requests, cambios de lockfiles y una revisión semanal. Estos controles se activarán en GitHub cuando se publiquen los cambios y estén habilitados allí; no se ejecutó ni publicó un workflow remoto durante esta tarea.

Se mantiene la CSP de producción sin `unsafe-eval`: no se debilitó para ocultar posibles problemas de interfaz. La compatibilidad visual de Livewire/Alpine con esa política todavía necesita pruebas de navegador en staging y su solución funcional correspondiente si aparecen bloqueos.

## Límites que siguen abiertos

Los escáneres de Composer/npm comprueban avisos conocidos, no todas las vulnerabilidades posibles. No se ejecutó un pentest externo, no se auditó una instalación Python/ML activa ni las dependencias nativas Android con un escáner de plataforma, y no se verificaron permisos/datos de MySQL porque el servicio no estaba disponible. Tampoco se certificaron la infraestructura, firewall, copias de seguridad o credenciales de proveedores de producción.

Antes de abrir el servicio siguen siendo necesarios la configuración final segura, revisión de usuarios/permisos reales, validación del despliegue y de Android, y pruebas de negocio y seguridad sobre staging. Los pendientes funcionales generales del informe de completitud, como entrenamiento ML, FEL real o almacenamiento de imágenes en producción, no quedan automáticamente resueltos por esta actualización de seguridad.
