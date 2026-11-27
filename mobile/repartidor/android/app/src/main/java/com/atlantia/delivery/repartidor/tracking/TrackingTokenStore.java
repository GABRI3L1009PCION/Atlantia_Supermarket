package com.atlantia.delivery.repartidor.tracking;

import android.content.SharedPreferences;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;

import java.security.GeneralSecurityException;
import java.security.KeyStore;
import java.util.Arrays;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

/** Stores only authenticated ciphertext; the encryption key never leaves Android Keystore. */
final class TrackingTokenStore {
    private static final String ALIAS = "atlantia_tracking_token_v1";
    private static final String ENCRYPTED = "token_encrypted_v1";
    private static final String LEGACY = "token";

    private TrackingTokenStore() {}

    private static SecretKey key() throws GeneralSecurityException {
        try {
            KeyStore store = KeyStore.getInstance("AndroidKeyStore");
            store.load(null);
            if (store.containsAlias(ALIAS)) {
                return (SecretKey) store.getKey(ALIAS, null);
            }
            KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
            generator.init(new KeyGenParameterSpec.Builder(ALIAS,
                KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .build());
            return generator.generateKey();
        } catch (java.io.IOException e) {
            throw new GeneralSecurityException("Keystore unavailable", e);
        }
    }

    static void save(SharedPreferences prefs, String token) throws GeneralSecurityException {
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
        cipher.init(Cipher.ENCRYPT_MODE, key());
        byte[] encrypted = cipher.doFinal(token.getBytes(java.nio.charset.StandardCharsets.UTF_8));
        byte[] iv = cipher.getIV();
        byte[] combined = new byte[iv.length + encrypted.length];
        System.arraycopy(iv, 0, combined, 0, iv.length);
        System.arraycopy(encrypted, 0, combined, iv.length, encrypted.length);
        if (! prefs.edit().putString(ENCRYPTED, Base64.encodeToString(combined, Base64.NO_WRAP)).remove(LEGACY).commit()) {
            throw new GeneralSecurityException("Cannot persist encrypted token");
        }
    }

    static String read(SharedPreferences prefs) throws GeneralSecurityException {
        String encrypted = prefs.getString(ENCRYPTED, "");
        if (encrypted.isEmpty()) {
            // Do not reuse plaintext tokens left by older versions; require a fresh login.
            if (! prefs.edit().remove(LEGACY).commit()) {
                throw new GeneralSecurityException("Cannot remove legacy token");
            }
            return "";
        }
        try {
            byte[] combined = Base64.decode(encrypted, Base64.NO_WRAP);
            if (combined.length < 29) {
                throw new GeneralSecurityException("Invalid encrypted token");
            }
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE, key(), new GCMParameterSpec(128, Arrays.copyOfRange(combined, 0, 12)));
            return new String(cipher.doFinal(Arrays.copyOfRange(combined, 12, combined.length)),
                java.nio.charset.StandardCharsets.UTF_8);
        } catch (IllegalArgumentException e) {
            throw new GeneralSecurityException("Invalid encrypted token", e);
        }
    }

    static void clear(SharedPreferences prefs) {
        prefs.edit().remove(ENCRYPTED).remove(LEGACY).apply();
    }
}
