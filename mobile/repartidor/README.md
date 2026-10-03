# Atlantia Repartidor

Aplicacion hibrida Android para repartidores de Atlantia Delivery. Usa Capacitor para compilar una app instalada y consume la API Laravel en `/api/repartidor`.

## Requisitos

- Node.js 20 o superior.
- Android Studio con SDK actual.
- Backend Laravel levantado y migraciones ejecutadas.
- Passport configurado con sus llaves y clientes.

## Instalacion

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

## Funciones listas en esta base

- Login seguro de repartidor con token Passport.
- Registro del dispositivo y token FCM.
- Estado En linea / Desconectado.
- Dashboard con ganancias, nivel, calificacion y pedidos.
- Ofertas entrantes con aceptar o rechazar.
- Flujo de pedido interno: llegue al comercio, pedido recogido, llegue al cliente y entrega con codigo.
- GPS nativo en segundo plano mediante ForegroundService Android.
- Notificaciones push y notificacion local de pedido entrante.

## GPS nativo

La app incluye `AtlantiaCourierTrackingPlugin` y `LocationForegroundService`. Cuando el repartidor pulsa `Iniciar GPS`, Android muestra una notificacion persistente y envia ubicacion a `/api/repartidor/gps` cada 15 segundos, incluso con la pantalla bloqueada.
