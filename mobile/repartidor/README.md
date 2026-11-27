# Atlantia Repartidor

Aplicacion hibrida Android para repartidores de Atlantia Delivery. Usa Capacitor para compilar una app instalada y consume la API Laravel en `/api/repartidor`.

## Requisitos

- Node.js 20 o superior.
- Android Studio con SDK actual.
- Backend Laravel levantado y migraciones ejecutadas.
- Passport configurado con sus llaves y clientes.

## Instalacion

La configuración segura usa HTTPS por defecto. Para una prueba local por HTTP,
establece `ATLANTIA_MOBILE_DEBUG_HTTP=true` antes de `npm run cap:sync` y compila
la variante **debug**. El manifiesto de release siempre prohíbe HTTP.
Al actualizar desde una versión que guardaba el token GPS sin cifrar, inicia
sesión nuevamente; el token antiguo no se reutiliza.

La firma release se configura exclusivamente con `ATLANTIA_ANDROID_KEYSTORE`,
`ATLANTIA_ANDROID_STORE_PASSWORD`, `ATLANTIA_ANDROID_KEY_ALIAS` y
`ATLANTIA_ANDROID_KEY_PASSWORD`. Sin esas variables, `preReleaseBuild` se detiene.
No guardes la clave de firma ni sus contraseñas en Git. Ejecuta las pruebas
instrumentadas `TrackingTokenStoreTest` en un dispositivo/emulador antes de publicar.

```bash
cd mobile/repartidor
npm install
npm run cap:sync
cd android
./gradlew assembleDebug
```

APK debug generado para prueba local:

```text
mobile/repartidor/dist/Atlantia-Repartidor-debug.apk
```

La app ya no depende de una IP fija para pruebas locales. Al iniciar sesion busca automaticamente el backend usando `/api/repartidor/ping`, recuerda el ultimo servidor valido y, si la IP cambia, escanea la red local comun.

El backend local debe estar levantado con:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Si el escaneo automatico falla por firewall o aislamiento del router, escribe en `Servidor` solo la IP actual de la PC, por ejemplo:

```text
192.168.0.7
```

La app la convierte automaticamente a `http://192.168.0.7:8000/api/repartidor`.

Para produccion se debe configurar un dominio HTTPS estable, por ejemplo `https://api.tudominio.com/api/repartidor`, en lugar de una IP local.

## Compilar sin Android Studio (Windows)

Gradle usa `JAVA_HOME` (JDK 21) y `android/local.properties` (`sdk.dir`). No se fija
una ruta de Java en `gradle.properties`, para que compile en cualquier PC o en CI.

```powershell
$env:JAVA_HOME = "C:\ruta\a\jdk-21"
$env:ATLANTIA_MOBILE_DEBUG_HTTP = "true"   # solo para el APK debug de red local
npx cap sync android
cd android
.\gradlew.bat assembleDebug
```

Para un release, vuelve a ejecutar `npx cap sync android` sin `ATLANTIA_MOBILE_DEBUG_HTTP`
antes de `assembleRelease`; de lo contrario el APK quedaria con esquema HTTP.

## Funciones listas en esta base

- Login seguro de repartidor con token Passport.
- Registro del dispositivo y token FCM.
- Estado En linea / Desconectado.
- Dashboard con ganancias, nivel, calificacion y pedidos.
- Ofertas entrantes con aceptar o rechazar.
- Flujo de entrega paso a paso (interno y externo): llegue al comercio, pedido no listo, pedido recogido, llegue al cliente, cobro y entrega con codigo y foto opcional.
- Confirmacion de cobro al entregar: efectivo, POS (No. de autorizacion) o transferencia (referencia). El backend marca el pedido como pagado o en validacion.
- Pantalla de cierre con la ganancia de la entrega.
- Ganancias: disponible, historial, solicitud de retiro, cuenta bancaria y liquidacion de efectivo.
- Soporte: reportes con tipo, historial de reportes, llamada/WhatsApp y alerta de emergencia con ubicacion.
- GPS nativo en segundo plano mediante ForegroundService Android.
- Notificaciones push y notificacion local de pedido entrante.

## GPS nativo

La app incluye `AtlantiaCourierTrackingPlugin` y `LocationForegroundService`. Cuando el repartidor pulsa `Iniciar GPS`, Android muestra una notificacion persistente y envia ubicacion a `/api/repartidor/gps` cada 15 segundos, incluso con la pantalla bloqueada.
