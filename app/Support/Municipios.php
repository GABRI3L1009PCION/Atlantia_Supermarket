<?php

namespace App\Support;

/**
 * Municipios donde opera Atlantia hoy (ATLANTIA_MARKETPLACE_MUNICIPIOS).
 * Para crecer a otro municipio: agregarlo ahí y crear sus zonas de entrega en el admin.
 */
final class Municipios
{
    /**
     * @return array<int, string>
     */
    public static function operativos(): array
    {
        $municipios = array_values(array_filter((array) config('atlantia.marketplace.municipios', [])));

        return $municipios === [] ? ['Puerto Barrios', 'Santo Tomas'] : $municipios;
    }

    /**
     * Nombre para mostrar (Santo Tomas → Santo Tomás de Castilla).
     */
    public static function nombre(?string $municipio): string
    {
        return match ((string) $municipio) {
            'Santo Tomas', 'Santo Tomás' => 'Santo Tomás de Castilla',
            default => (string) $municipio,
        };
    }

    /**
     * "Puerto Barrios y Santo Tomás de Castilla".
     */
    public static function texto(): string
    {
        $nombres = array_map(self::nombre(...), self::operativos());
        $ultimo = array_pop($nombres);

        return $nombres === [] ? (string) $ultimo : implode(', ', $nombres).' y '.$ultimo;
    }
}
