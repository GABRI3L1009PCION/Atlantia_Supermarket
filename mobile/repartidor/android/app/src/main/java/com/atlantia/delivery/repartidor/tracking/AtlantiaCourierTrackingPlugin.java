package com.atlantia.delivery.repartidor.tracking;

import android.Manifest;
import android.content.Intent;
import android.os.Build;

import androidx.core.content.ContextCompat;

import com.getcapacitor.JSObject;
import com.getcapacitor.PermissionState;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;

@CapacitorPlugin(
    name = "AtlantiaCourierTracking",
    permissions = {
        @Permission(
            alias = "location",
            strings = {
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            }
        ),
        @Permission(
            alias = "notifications",
            strings = {
                Manifest.permission.POST_NOTIFICATIONS
            }
        )
    }
)
public class AtlantiaCourierTrackingPlugin extends Plugin {
    @PluginMethod
    public void start(PluginCall call) {
        if (!hasLocationPermission() || needsNotificationPermission()) {
            requestNeededPermissions(call);
            return;
        }

        startService(call);
    }

    @PluginMethod
    public void stop(PluginCall call) {
        Intent intent = new Intent(getContext(), LocationForegroundService.class);
        intent.setAction(LocationForegroundService.ACTION_STOP);
        getContext().startService(intent);

        JSObject result = new JSObject();
        result.put("running", false);
        call.resolve(result);
    }

    @PluginMethod
    public void isRunning(PluginCall call) {
        JSObject result = new JSObject();
        result.put("running", LocationForegroundService.isRunning(getContext()));
        call.resolve(result);
    }

    @PermissionCallback
    private void trackingPermissionCallback(PluginCall call) {
        if (!hasLocationPermission()) {
            call.reject("Permiso de ubicacion requerido para iniciar seguimiento.");
            return;
        }

        if (needsNotificationPermission()) {
            call.reject("Permiso de notificaciones requerido para mantener el servicio activo.");
            return;
        }

        startService(call);
    }

    private void requestNeededPermissions(PluginCall call) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            requestPermissionForAliases(new String[] { "location", "notifications" }, call, "trackingPermissionCallback");
            return;
        }

        requestPermissionForAlias("location", call, "trackingPermissionCallback");
    }

    private boolean hasLocationPermission() {
        return getPermissionState("location") == PermissionState.GRANTED;
    }

    private boolean needsNotificationPermission() {
        return Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU
            && getPermissionState("notifications") != PermissionState.GRANTED;
    }

    private void startService(PluginCall call) {
        String apiBaseUrl = call.getString("apiBaseUrl");
        String token = call.getString("token");

        if (apiBaseUrl == null || apiBaseUrl.trim().isEmpty()) {
            call.reject("apiBaseUrl es requerido.");
            return;
        }

        if (token == null || token.trim().isEmpty()) {
            call.reject("token es requerido.");
            return;
        }

        Intent intent = new Intent(getContext(), LocationForegroundService.class);
        intent.setAction(LocationForegroundService.ACTION_START);
        intent.putExtra(LocationForegroundService.EXTRA_API_BASE_URL, apiBaseUrl);
        intent.putExtra(LocationForegroundService.EXTRA_TOKEN, token);
        intent.putExtra(LocationForegroundService.EXTRA_INTERVAL_MS, call.getInt("intervalMs", 15000));
        intent.putExtra(LocationForegroundService.EXTRA_ESTADO, call.getString("estado", "disponible"));

        ContextCompat.startForegroundService(getContext(), intent);

        JSObject result = new JSObject();
        result.put("running", true);
        call.resolve(result);
    }
}
