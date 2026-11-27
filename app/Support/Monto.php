<?php

namespace App\Support;

/**
 * Lee montos escritos por personas: "Q 149", "149,50", "1,250.50" o "1.250,50".
 * Devuelve el número como texto con punto decimal, o el texto original si no es un monto
 * (para que la validación muestre el error normal).
 */
final class Monto
{
    public static function normalizar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $original = trim((string) $valor);
        $limpio = (string) preg_replace('/[^\d.,]/', '', $original);

        if ($limpio === '') {
            return $original === '' ? '' : $original;
        }

        $ultimaComa = strrpos($limpio, ',');
        $ultimoPunto = strrpos($limpio, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            /* El último separador es el decimal; el otro es de miles. */
            $limpio = $ultimaComa > $ultimoPunto
                ? str_replace(',', '.', str_replace('.', '', $limpio))
                : str_replace(',', '', $limpio);
        } elseif ($ultimaComa !== false) {
            /* "1,250" es miles; "149,5" o "149,50" es decimal. */
            $limpio = preg_match('/^\d+(,\d{3})+$/', $limpio) === 1
                ? str_replace(',', '', $limpio)
                : str_replace(',', '.', $limpio);
        }

        return $limpio;
    }
}
