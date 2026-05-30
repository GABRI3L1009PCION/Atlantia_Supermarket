<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Servicio TOTP compatible con apps autenticadoras estandar.
 */
class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera un secreto base32 para TOTP.
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    /**
     * Genera el codigo TOTP para un instante dado.
     */
    public function codeAt(string $secret, ?int $timestamp = null, int $digits = 6, int $period = 30): string
    {
        $counter = intdiv($timestamp ?? time(), $period);
        $binarySecret = $this->decodeBase32($secret);
        $binaryCounter = pack('N2', 0, $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $binarySecret, true);

        $offset = ord(substr($hash, -1)) & 0x0f;
        $chunk = substr($hash, $offset, 4);
        $value = unpack('N', $chunk)[1] & 0x7fffffff;
        $otp = $value % (10 ** $digits);

        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica un codigo TOTP con tolerancia de ventanas.
     */
    public function verify(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
    {
        $normalized = preg_replace('/\D/', '', $code ?? '');

        if (! is_string($normalized) || strlen($normalized) !== $digits) {
            return false;
        }

        $timestamp = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = $this->codeAt($secret, $timestamp + ($offset * $period), $digits, $period);

            if (hash_equals($candidate, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construye la URI otpauth para apps autenticadoras.
     */
    public function provisioningUri(string $issuer, string $account, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);

        return 'otpauth://totp/' . $label . '?' . $query;
    }

    /**
     * Formatea la clave manual por bloques para hacerla legible.
     */
    public function humanizeSecret(string $secret, int $group = 4): string
    {
        return trim(chunk_split(Str::upper($secret), $group, ' '));
    }

    /**
     * Decodifica una cadena base32 a binario.
     */
    private function decodeBase32(string $encoded): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');

        if ($normalized === '') {
            throw new RuntimeException('Secreto TOTP invalido.');
        }

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($normalized) as $character) {
            $value = strpos(self::BASE32_ALPHABET, $character);

            if ($value === false) {
                throw new RuntimeException('Secreto TOTP invalido.');
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            while ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $output;
    }
}
