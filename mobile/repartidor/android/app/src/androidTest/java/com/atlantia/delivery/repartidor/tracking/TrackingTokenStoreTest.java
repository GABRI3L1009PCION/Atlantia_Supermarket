package com.atlantia.delivery.repartidor.tracking;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Base64;
import androidx.test.ext.junit.runners.AndroidJUnit4;
import androidx.test.platform.app.InstrumentationRegistry;
import org.junit.After;
import org.junit.Before;
import org.junit.Test;
import org.junit.runner.RunWith;
import java.security.GeneralSecurityException;
import static org.junit.Assert.*;

/** Must run on a device/emulator: exercises the real Android Keystore and GCM authentication. */
@RunWith(AndroidJUnit4.class)
public class TrackingTokenStoreTest {
    private SharedPreferences prefs;

    @Before public void prepare() {
        Context context = InstrumentationRegistry.getInstrumentation().getTargetContext();
        prefs = context.getSharedPreferences("tracking_security_test_" + java.util.UUID.randomUUID(), Context.MODE_PRIVATE);
    }

    @After public void cleanup() {
        prefs.edit().clear().commit();
    }

    @Test public void storesCiphertextAndRoundTripsToken() throws Exception {
        prefs.edit().putString("token", "legacy-plaintext").commit();
        TrackingTokenStore.save(prefs, "test-bearer-token");
        assertFalse(prefs.contains("token"));
        assertFalse(prefs.getString("token_encrypted_v1", "").contains("test-bearer-token"));
        assertEquals("test-bearer-token", TrackingTokenStore.read(prefs));
        TrackingTokenStore.clear(prefs);
        assertEquals("", TrackingTokenStore.read(prefs));
    }

    @Test public void legacyPlaintextIsRemovedInsteadOfReused() throws Exception {
        prefs.edit().putString("token", "legacy-plaintext").commit();
        assertEquals("", TrackingTokenStore.read(prefs));
        assertFalse(prefs.contains("token"));
    }

    @Test public void rejectsTamperedCiphertext() throws Exception {
        TrackingTokenStore.save(prefs, "test-bearer-token");
        byte[] data = Base64.decode(prefs.getString("token_encrypted_v1", ""), Base64.NO_WRAP);
        data[data.length - 1] ^= 1;
        prefs.edit().putString("token_encrypted_v1", Base64.encodeToString(data, Base64.NO_WRAP)).commit();
        try {
            TrackingTokenStore.read(prefs);
            fail("Tampered ciphertext must be rejected.");
        } catch (GeneralSecurityException expected) {
            // GCM authentication failed: no token was returned.
        }
    }
}
