<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Regla única de contraseña segura para todo Atlantia, pensada para que cualquier
 * persona la entienda:
 *
 * - Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.
 * - No puede aparecer en filtraciones conocidas (Have I Been Pwned). Solo se envían
 *   los 5 primeros caracteres del hash SHA-1, nunca la contraseña. Si el servicio no
 *   responde, no se bloquea al usuario.
 */
class ContrasenaSegura implements ValidationRule
{
    public const MINIMO = 8;

    public const MAXIMO = 128;

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $clave = (string) $value;

        if (mb_strlen($clave) < self::MINIMO) {
            $fail('La contraseña debe tener al menos '.self::MINIMO.' caracteres.');

            return;
        }

        if (mb_strlen($clave) > self::MAXIMO) {
            $fail('La contraseña no puede pasar de '.self::MAXIMO.' caracteres.');

            return;
        }

        $faltan = array_keys(array_filter([
            'una minúscula' => ! preg_match('/\p{Ll}/u', $clave),
            'una mayúscula' => ! preg_match('/\p{Lu}/u', $clave),
            'un número' => ! preg_match('/\d/', $clave),
            'un símbolo (! # $ %)' => ! preg_match('/[^\p{L}\d]/u', $clave),
        ]));

        if ($faltan !== []) {
            $fail('A la contraseña le falta '.$this->enumerar($faltan).'.');

            return;
        }

        if ($this->estaFiltrada($clave)) {
            $fail('Esta contraseña ya fue robada en otros sitios de internet y no es segura. Por favor escribe otra diferente.');
        }
    }

    private function estaFiltrada(string $clave): bool
    {
        if (! config('atlantia.auth.verificar_contrasenas_filtradas', true)) {
            return false;
        }

        try {
            return ! app(UncompromisedVerifier::class)->verify(['value' => $clave, 'threshold' => 0]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int, string>  $partes
     */
    private function enumerar(array $partes): string
    {
        if (count($partes) === 1) {
            return $partes[0];
        }

        $ultima = array_pop($partes);

        return implode(', ', $partes).' y '.$ultima;
    }
}
