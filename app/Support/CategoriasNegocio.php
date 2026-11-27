<?php

namespace App\Support;

/**
 * Categorías de negocio que un vendedor puede elegir para su tienda (hasta MAXIMO).
 * Se guardan las claves; al público siempre se muestra la etiqueta.
 */
final class CategoriasNegocio
{
    public const MAXIMO = 3;

    /** @var array<string, string> */
    public const OPCIONES = [
        'abarrotes' => 'Abarrotes y supermercado',
        'alimentos_frescos' => 'Frutas, verduras y carnes',
        'panaderia_reposteria' => 'Panadería y repostería',
        'comida_preparada' => 'Comida preparada',
        'bebidas_licores' => 'Bebidas y licores',
        'cosmeticos' => 'Belleza y cuidado personal',
        'farmacia' => 'Farmacia y salud',
        'hogar_limpieza' => 'Hogar y limpieza',
        'ropa_accesorios' => 'Ropa y accesorios',
        'electronica' => 'Electrónica y celulares',
        'ferreteria' => 'Ferretería',
        'mascotas' => 'Mascotas',
        'papeleria' => 'Librería y papelería',
        'artesania' => 'Artesanías y regalos',
        'servicios' => 'Servicios',
    ];

    /**
     * @return array<int, string>
     */
    public static function claves(): array
    {
        return array_keys(self::OPCIONES);
    }

    /**
     * Etiqueta legible. Si el valor no es una clave conocida (texto libre de "Otro"), se devuelve tal cual.
     */
    public static function etiqueta(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return self::OPCIONES[$valor] ?? $valor;
    }

    /**
     * Convierte un texto antiguo (clave o etiqueta) en clave, si se reconoce.
     */
    public static function claveDe(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if (isset(self::OPCIONES[$valor])) {
            return $valor;
        }

        $buscado = mb_strtolower($valor);
        foreach (self::OPCIONES as $clave => $etiqueta) {
            if (mb_strtolower($etiqueta) === $buscado) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * Claves cuya etiqueta contiene el texto buscado (para el buscador de tiendas).
     *
     * @return array<int, string>
     */
    public static function buscar(string $texto): array
    {
        $texto = mb_strtolower(trim($texto));

        if ($texto === '') {
            return [];
        }

        return array_keys(array_filter(
            self::OPCIONES,
            static fn (string $etiqueta): bool => str_contains(mb_strtolower($etiqueta), $texto),
        ));
    }
}
