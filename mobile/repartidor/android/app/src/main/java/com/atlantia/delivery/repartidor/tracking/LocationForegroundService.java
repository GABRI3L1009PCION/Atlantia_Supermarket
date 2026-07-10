package com.atlantia.delivery.repartidor.tracking;

import android.Manifest;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import android.os.PowerManager;

import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;
import androidx.core.content.ContextCompat;

import com.atlantia.delivery.repartidor.MainActivity;
import com.atlantia.delivery.repartidor.R;

import org.json.JSONObject;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.TimeZone;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class LocationForegroundService extends Service {
    public static final String ACTION_START = "com.atlantia.delivery.repartidor.tracking.START";
    public static final String ACTION_STOP = "com.atlantia.delivery.repartidor.tracking.STOP";
    public static final String EXTRA_API_BASE_URL = "apiBaseUrl";
    public static final String EXTRA_TOKEN = "token";
    public static final String EXTRA_INTERVAL_MS = "intervalMs";
    public static final String EXTRA_ESTADO = "estado";

    private static final String CHANNEL_ID = "atlantia_courier_tracking";
    private static final int NOTIFICATION_ID = 4101;
    private static final String PREFS_NAME = "atlantia_courier_tracking";
    private static final String PREF_RUNNING = "running";
    private static final String PREF_API_BASE_URL = "apiBaseUrl";
    private static final String PREF_TOKEN = "token";
    private static final String PREF_INTERVAL_MS = "intervalMs";
    private static final String PREF_ESTADO = "estado";

    private final Handler handler = new Handler(Looper.getMainLooper());
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private LocationManager locationManager;
    private Location lastLocation;
    private PowerManager.WakeLock wakeLock;
    private String apiBaseUrl;
    private String token;
    private String estado = "disponible";
    private int intervalMs = 15000;

    private final LocationListener listener = new LocationListener() {
        @Override
        public void onLocationChanged(Location location) {
            lastLocation = location;
            postLocation(location);
        }

        @Override
        public void onProviderEnabled(String provider) {
        }

        @Override
        public void onProviderDisabled(String provider) {
        }

        @Override
        public void onStatusChanged(String provider, int status, Bundle extras) {
        }
    };

    private final Runnable tick = new Runnable() {
        @Override
        public void run() {
            Location location = lastLocation != null ? lastLocation : bestLastKnownLocation();
            if (location != null) {
                postLocation(location);
            }
            handler.postDelayed(this, intervalMs);
        }
    };

    @Override
    public void onCreate() {
        super.onCreate();
        locationManager = (LocationManager) getSystemService(Context.LOCATION_SERVICE);
        createNotificationChannel();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        if (intent != null && ACTION_STOP.equals(intent.getAction())) {
            stopTracking();
            stopSelf();
            return START_NOT_STICKY;
        }

        loadConfig(intent);
        startForeground(NOTIFICATION_ID, buildNotification());
        startTracking();

        return START_STICKY;
    }

    @Nullable
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }

    @Override
    public void onDestroy() {
        stopTracking();
        executor.shutdownNow();
        super.onDestroy();
    }

    public static boolean isRunning(Context context) {
        return context
            .getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
            .getBoolean(PREF_RUNNING, false);
    }

    private void loadConfig(@Nullable Intent intent) {
        SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);

        apiBaseUrl = valueOrSaved(intent, EXTRA_API_BASE_URL, prefs.getString(PREF_API_BASE_URL, ""));
        token = valueOrSaved(intent, EXTRA_TOKEN, prefs.getString(PREF_TOKEN, ""));
        estado = valueOrSaved(intent, EXTRA_ESTADO, prefs.getString(PREF_ESTADO, "disponible"));
        intervalMs = intent != null
            ? intent.getIntExtra(EXTRA_INTERVAL_MS, prefs.getInt(PREF_INTERVAL_MS, 15000))
            : prefs.getInt(PREF_INTERVAL_MS, 15000);
        intervalMs = Math.max(10000, intervalMs);

        prefs.edit()
            .putBoolean(PREF_RUNNING, true)
            .putString(PREF_API_BASE_URL, apiBaseUrl)
            .putString(PREF_TOKEN, token)
            .putString(PREF_ESTADO, estado)
            .putInt(PREF_INTERVAL_MS, intervalMs)
            .apply();
    }

    private String valueOrSaved(@Nullable Intent intent, String key, String fallback) {
        if (intent == null) {
            return fallback;
        }

        String value = intent.getStringExtra(key);
        return value == null || value.trim().isEmpty() ? fallback : value;
    }

    private void startTracking() {
        if (!hasLocationPermission()) {
            stopSelf();
            return;
        }

        acquireWakeLock();
        requestUpdates(LocationManager.GPS_PROVIDER);
        requestUpdates(LocationManager.NETWORK_PROVIDER);

        lastLocation = bestLastKnownLocation();
        handler.removeCallbacks(tick);
        handler.post(tick);
    }

    private void stopTracking() {
        handler.removeCallbacks(tick);
        if (locationManager != null) {
            try {
                locationManager.removeUpdates(listener);
            } catch (SecurityException ignored) {
            }
        }

        releaseWakeLock();
        getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
            .edit()
            .putBoolean(PREF_RUNNING, false)
            .apply();
    }

    private void requestUpdates(String provider) {
        if (!hasLocationPermission() || locationManager == null) {
            return;
        }

        try {
            if (locationManager.isProviderEnabled(provider)) {
                locationManager.requestLocationUpdates(provider, intervalMs, 0, listener, Looper.getMainLooper());
            }
        } catch (IllegalArgumentException | SecurityException ignored) {
        }
    }

    @Nullable
    private Location bestLastKnownLocation() {
        if (!hasLocationPermission() || locationManager == null) {
            return null;
        }

        Location best = null;
        for (String provider : locationManager.getProviders(true)) {
            try {
                Location location = locationManager.getLastKnownLocation(provider);
                if (location == null) {
                    continue;
                }
                if (best == null || location.getAccuracy() < best.getAccuracy()) {
                    best = location;
                }
            } catch (SecurityException ignored) {
            }
        }

        return best;
    }

    private boolean hasLocationPermission() {
        return ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
            || ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
    }

    private void postLocation(Location location) {
        if (apiBaseUrl == null || apiBaseUrl.trim().isEmpty() || token == null || token.trim().isEmpty()) {
            return;
        }

        executor.execute(() -> {
            HttpURLConnection connection = null;
            try {
                URL url = new URL(trimSlash(apiBaseUrl) + "/gps");
                JSONObject payload = new JSONObject();
                payload.put("latitude", location.getLatitude());
                payload.put("longitude", location.getLongitude());
                payload.put("accuracy_meters", location.hasAccuracy() ? location.getAccuracy() : JSONObject.NULL);
                payload.put("timestamp_gps", isoNow(location.getTime()));
                payload.put("estado", estado);

                byte[] body = payload.toString().getBytes(StandardCharsets.UTF_8);
                connection = (HttpURLConnection) url.openConnection();
                connection.setRequestMethod("POST");
                connection.setConnectTimeout(10000);
                connection.setReadTimeout(10000);
                connection.setDoOutput(true);
                connection.setRequestProperty("Accept", "application/json");
                connection.setRequestProperty("Content-Type", "application/json; charset=utf-8");
                connection.setRequestProperty("Authorization", "Bearer " + token);
                connection.setFixedLengthStreamingMode(body.length);

                try (OutputStream output = connection.getOutputStream()) {
                    output.write(body);
                }

                connection.getResponseCode();
            } catch (Exception ignored) {
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        });
    }

    private String trimSlash(String value) {
        return value.endsWith("/") ? value.substring(0, value.length() - 1) : value;
    }

    private String isoNow(long timestamp) {
        SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US);
        format.setTimeZone(TimeZone.getTimeZone("UTC"));
        return format.format(new Date(timestamp));
    }

    private void acquireWakeLock() {
        if (wakeLock != null && wakeLock.isHeld()) {
            return;
        }

        PowerManager powerManager = (PowerManager) getSystemService(Context.POWER_SERVICE);
        if (powerManager == null) {
            return;
        }

        wakeLock = powerManager.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "AtlantiaRepartidor:LocationTracking");
        wakeLock.setReferenceCounted(false);
        wakeLock.acquire();
    }

    private void releaseWakeLock() {
        if (wakeLock != null && wakeLock.isHeld()) {
            wakeLock.release();
        }
        wakeLock = null;
    }

    private Notification buildNotification() {
        Intent openIntent = new Intent(this, MainActivity.class);
        PendingIntent openPendingIntent = PendingIntent.getActivity(
            this,
            0,
            openIntent,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE
        );

        Intent stopIntent = new Intent(this, LocationForegroundService.class);
        stopIntent.setAction(ACTION_STOP);
        PendingIntent stopPendingIntent = PendingIntent.getService(
            this,
            1,
            stopIntent,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE
        );

        return new NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("Atlantia Repartidor")
            .setContentText("Seguimiento de ubicacion activo")
            .setSmallIcon(R.drawable.ic_stat_atlantia)
            .setContentIntent(openPendingIntent)
            .setOngoing(true)
            .setOnlyAlertOnce(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .addAction(R.drawable.ic_stat_atlantia, "Detener", stopPendingIntent)
            .build();
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            return;
        }

        NotificationChannel channel = new NotificationChannel(
            CHANNEL_ID,
            "Seguimiento Atlantia",
            NotificationManager.IMPORTANCE_LOW
        );
        channel.setDescription("Ubicacion en segundo plano para repartidores en linea.");

        NotificationManager manager = getSystemService(NotificationManager.class);
        if (manager != null) {
            manager.createNotificationChannel(channel);
        }
    }
}
