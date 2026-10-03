# Notas Android nativas

Estas notas documentan la parte Android nativa incluida en la app de reparto.

## Permisos necesarios

Incluidos en `android/app/src/main/AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_LOCATION" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.WAKE_LOCK" />
<uses-permission android:name="android.permission.VIBRATE" />
```

Android 13 o superior requiere permiso runtime para notificaciones. Android 14 requiere declarar el tipo del servicio en primer plano.

## Servicio de ubicacion en segundo plano

La app incluye `LocationForegroundService`, que:

- Mantenga una notificacion persistente mientras el repartidor esta en linea.
- Envie latitud, longitud, precision y fecha GPS a `/api/repartidor/gps`.
- Reintente cuando la conexion sea inestable.
- Detenga seguimiento cuando el repartidor quede desconectado.
- Use intervalos configurables de 10 a 20 segundos durante una entrega activa.

Declaracion incluida:

```xml
<service
    android:name=".tracking.LocationForegroundService"
    android:exported="false"
    android:foregroundServiceType="location" />
```

## Push notifications

Para FCM:

- Crear el proyecto en Firebase.
- Descargar `google-services.json`.
- Colocarlo en `android/app/google-services.json`.
- Agregar el plugin Gradle de Google Services cuando Capacitor genere el proyecto Android.

## Evidencia fotografica

La dependencia `@capacitor/camera` ya esta incluida. El backend debe recibir la foto como evidencia de entrega o incidencia en un endpoint dedicado antes de habilitarla en produccion.
