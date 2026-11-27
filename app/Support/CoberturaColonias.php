<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Cobertura propia de Atlantia por colonia o barrio (config atlantia.cobertura_colonias).
 *
 * - Detecta si una direccion cae en una colonia donde Atlantia no entrega.
 * - Ubica colonias que pertenecen a otro municipio (ej. Quebrada Seca es de Santo Tomas).
 */
final class CoberturaColonias
{
    /**
     * Devuelve el nombre de la colonia excluida que aparece en la direccion, o null si se cubre.
     *
     * En el campo "colonia" basta con que aparezca el nombre. En la direccion escrita
     * se exige "barrio/colonia/col./bo." antes del nombre para no confundir nombres de calles.
     */
    public static function excluida(?string $municipio, ?string $colonia, ?string $direccion = null): ?string
    {
        $catalogo = self::catalogo();
        $clave = self::municipioClave($municipio);
        $excluidas = $clave !== null ? ($catalogo[$clave]['excluidas'] ?? []) : [];

        $textoColonia = ' '.self::normalizar($colonia).' ';
        $textoDireccion = ' '.self::normalizar($direccion).' ';

        foreach ($excluidas as $nombre) {
            $termino = self::normalizar($nombre);

            if ($termino === '') {
                continue;
            }

            if (trim($textoColonia) !== '' && str_contains($textoColonia, ' '.$termino.' ')) {
                return $nombre;
            }

            if (preg_match('/\b(barrio|colonia|col|bo|bario)\s+'.preg_quote($termino, '/').'\b/', $textoDireccion) === 1) {
                return $nombre;
            }
        }

        return null;
    }

    /**
     * Si la colonia pertenece a otro municipio del catalogo, devuelve ese municipio.
     */
    public static function municipioDe(?string $colonia): ?string
    {
        $texto = ' '.self::normalizar($colonia).' ';

        if (trim($texto) === '') {
            return null;
        }

        foreach (self::catalogo() as $municipio => $listas) {
            foreach ($listas['cubiertas'] ?? [] as $nombre) {
                $termino = self::nucleo($nombre);
                if ($termino !== '' && str_contains($texto, ' '.$termino.' ')) {
                    return $municipio;
                }
            }
        }

        return null;
    }

    /**
     * Indica si el carrito incluye productos vendidos por Atlantia (inventario propio).
     *
     * @param  iterable<int, mixed>  $items  CarritoItem con producto.vendor
     */
    public static function incluyeProductosAtlantia(iterable $items): bool
    {
        $slug = (string) config('atlantia.vendor_propio_slug', 'atlantia-supermarket');

        foreach ($items as $item) {
            if ($item->producto?->vendor?->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Catalogo para el formulario: cubiertas y excluidas por municipio.
     *
     * @return array<string, array{cubiertas: array<int, string>, excluidas: array<int, string>}>
     */
    public static function catalogo(): array
    {
        return (array) config('atlantia.cobertura_colonias', []);
    }

    /**
     * Normaliza acentos, mayusculas y signos.
     */
    public static function normalizar(?string $valor): string
    {
        return (string) Str::of((string) $valor)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();
    }

    /**
     * Nombre sin "barrio/colonia" ni numerales romanos, para comparar con lo que escribe el cliente.
     */
    private static function nucleo(string $nombre): string
    {
        return (string) Str::of(self::normalizar($nombre))
            ->replaceMatches('/^(barrio|colonia)\s+/', '')
            ->replaceMatches('/\s+(i|ii|iii)(\s+(y|i|ii|iii))*$/', '')
            ->squish();
    }

    private static function municipioClave(?string $municipio): ?string
    {
        $buscado = self::normalizar($municipio);

        foreach (array_keys(self::catalogo()) as $clave) {
            if ($buscado !== '' && str_contains($buscado, self::normalizar($clave))) {
                return $clave;
            }
        }

        return null;
    }
}
