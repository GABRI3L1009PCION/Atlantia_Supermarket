package com.atlantia.delivery.repartidor;

import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;

import com.atlantia.delivery.repartidor.tracking.AtlantiaCourierTrackingPlugin;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(AtlantiaCourierTrackingPlugin.class);
        super.onCreate(savedInstanceState);
        WebView webView = getBridge().getWebView();
        if (webView != null) {
            webView.clearCache(true);
            webView.getSettings().setCacheMode(WebSettings.LOAD_NO_CACHE);
        }
    }
}
