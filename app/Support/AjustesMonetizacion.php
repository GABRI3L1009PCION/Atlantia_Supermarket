<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Precios de planes y publicidad editables desde el panel del administrador.
 *
 * config/monetizacion.php trae los valores de fábrica; lo que el administrador guarda
 * queda en la tabla ajustes_monetizacion y se aplica encima al arrancar la aplicación,
 * así todas las pantallas y cobros usan el mismo precio.
 */
class AjustesMonetizacion
{
    public const CACHE = 'ajustes_monetizacion.v1';

    /** Campos de cada plan que se pueden cambiar. */
    public const CAMPOS_PLAN = ['precio', 'comision', 'max_productos', 'max_promociones', 'carga_masiva', 'destacados_incluidos', 'banners_incluidos', 'resumen', 'beneficios'];

    /** Valores originales de config, antes de aplicar ajustes (para "restaurar"). */
    private static ?array $fabrica = null;

    /**
     * Aplica lo guardado sobre la configuración en memoria. Si la tabla aún no existe
     * (instalación nueva, pruebas antes de migrar) se queda con los valores de fábrica.
     */
    public static function aplicar(): void
    {
        self::$fabrica ??= [
            'planes' => (array) config('monetizacion.planes', []),
            'banners' => (array) config('monetizacion.banners', []),
            'destacado' => (array) config('monetizacion.destacado', []),
            'patrocinado' => (array) config('monetizacion.patrocinado', []),
            'apertura' => (array) config('monetizacion.apertura', []),
        ];

        try {
            $ajustes = Cache::rememberForever(self::CACHE, fn (): array => DB::table('ajustes_monetizacion')
                ->pluck('valor', 'clave')
                ->map(fn ($valor) => json_decode((string) $valor, true) ?: [])
                ->all());
        } catch (Throwable) {
            return;
        }

        $planes = self::$fabrica['planes'];
        foreach ((array) ($ajustes['planes'] ?? []) as $clave => $cambios) {
            if (! isset($planes[$clave]) || ! is_array($cambios)) {
                continue;
            }
            $planes[$clave] = array_replace($planes[$clave], array_intersect_key($cambios, array_flip(self::CAMPOS_PLAN)));
        }
        config(['monetizacion.planes' => $planes]);

        $publicidad = (array) ($ajustes['publicidad'] ?? []);
        $banners = self::$fabrica['banners'];
        foreach ((array) ($publicidad['banners'] ?? []) as $posicion => $precios) {
            if (isset($banners['posiciones'][$posicion]) && is_array($precios)) {
                $banners['posiciones'][$posicion] = array_replace($banners['posiciones'][$posicion], array_intersect_key($precios, ['semana' => 1, 'mes' => 1]));
            }
        }
        config([
            'monetizacion.banners' => $banners,
            'monetizacion.destacado' => array_replace(self::$fabrica['destacado'], isset($publicidad['destacado_semana']) ? ['semana' => $publicidad['destacado_semana']] : []),
            'monetizacion.patrocinado' => array_replace(self::$fabrica['patrocinado'], isset($publicidad['patrocinado_semana']) ? ['semana' => $publicidad['patrocinado_semana']] : []),
            'monetizacion.apertura' => array_replace(self::$fabrica['apertura'], array_intersect_key((array) ($ajustes['apertura'] ?? []), ['activa' => 1, 'dias' => 1, 'hasta' => 1])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fabrica(string $seccion): array
    {
        self::aplicar();

        return (array) (self::$fabrica[$seccion] ?? []);
    }

    public static function guardar(string $clave, array $valor, ?int $usuarioId): void
    {
        $existe = DB::table('ajustes_monetizacion')->where('clave', $clave)->exists();
        $fila = ['valor' => json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'updated_by' => $usuarioId, 'updated_at' => now()];

        $existe
            ? DB::table('ajustes_monetizacion')->where('clave', $clave)->update($fila)
            : DB::table('ajustes_monetizacion')->insert($fila + ['clave' => $clave, 'created_at' => now()]);

        self::olvidar();
    }

    public static function borrar(string $clave): void
    {
        DB::table('ajustes_monetizacion')->where('clave', $clave)->delete();
        self::olvidar();
    }

    /**
     * @return array<string, mixed>
     */
    public static function guardado(string $clave): array
    {
        $valor = DB::table('ajustes_monetizacion')->where('clave', $clave)->value('valor');

        return $valor ? (json_decode((string) $valor, true) ?: []) : [];
    }

    private static function olvidar(): void
    {
        Cache::forget(self::CACHE);
        self::aplicar();
    }

    /**
     * Ajusta las líneas de beneficios que mencionan números del plan (productos,
     * promociones, comisión) para que no queden diciendo algo distinto al precio nuevo.
     *
     * @param  array<int, string>  $beneficios
     * @param  array<string, mixed>  $plan
     * @return array<int, string>
     */
    public static function sincronizarBeneficios(array $beneficios, array $plan): array
    {
        return array_values(array_map(function (string $linea) use ($plan): string {
            if (($plan['max_productos'] ?? null) !== null && preg_match('/^Hasta [\d,.]+ productos$/u', $linea)) {
                return 'Hasta '.number_format((int) $plan['max_productos']).' productos';
            }
            if (preg_match('/^(\d+ promoci(ón|ones) activas?|Promociones ilimitadas)(.*)$/u', $linea, $m)) {
                $max = $plan['max_promociones'] ?? null;
                $texto = $max === null ? 'Promociones ilimitadas' : ($max === 1 ? '1 promoción activa' : $max.' promociones activas');

                return $texto.$m[3];
            }
            if (preg_match('/^Q ?[\d,.]+ al mes \+ [\d,.]+ ?% por venta$/u', $linea)) {
                return 'Q '.self::numero($plan['precio'] ?? 0).' al mes + '.self::numero($plan['comision'] ?? 0).' % por venta';
            }
            if ($linea === 'Sin comisión por venta' && (float) ($plan['comision'] ?? 0) > 0) {
                return self::numero($plan['comision']).' % de comisión por venta';
            }
            if (preg_match('/^[\d,.]+ ?% de comisión por venta$/u', $linea)) {
                return (float) ($plan['comision'] ?? 0) > 0 ? self::numero($plan['comision']).' % de comisión por venta' : 'Sin comisión por venta';
            }

            return $linea;
        }, $beneficios));
    }

    private static function numero(mixed $valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
    }
}
