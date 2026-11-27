<?php

namespace App\Services\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Support\Iva;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Carga masiva de productos desde un CSV (Excel: "Guardar como CSV").
 *
 * Cada fila se identifica por su SKU dentro de la tienda:
 *  - si el SKU ya existe, se actualizan solo las columnas con valor (precio, existencias, etc.);
 *  - si no existe, se crea el producto (requiere nombre, categoria y precio).
 * Los precios son FINALES (lo que paga el cliente, con IVA), igual que en el formulario.
 * Por compatibilidad también se aceptan las columnas viejas precio_sin_iva / oferta_sin_iva (se les suma el 12 %).
 */
class ImportadorProductosService
{
    public const COLUMNAS = [
        'sku', 'nombre', 'categoria', 'precio', 'oferta', 'existencias',
        'unidad_medida', 'codigo_barras', 'descripcion', 'peso_gramos', 'visible',
    ];

    private const UNIDADES = ['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'];

    private const MAX_FILAS = 5000;

    /**
     * Procesa el archivo y devuelve el resumen.
     *
     * @return array{creados: int, actualizados: int, errores: array<int, string>}
     */
    public function importar(Vendor $vendor, string $ruta): array
    {
        $resumen = ['creados' => 0, 'actualizados' => 0, 'errores' => []];
        $filas = $this->leer($ruta);

        if ($filas === null) {
            $resumen['errores'][] = 'El archivo está vacío o no tiene la columna "sku". Descarga la plantilla y vuelve a intentarlo.';

            return $resumen;
        }

        $categorias = Categoria::query()->where('is_active', true)->get(['id', 'nombre', 'slug']);

        foreach ($filas as $numero => $fila) {
            if (count($resumen['errores']) >= 50) {
                $resumen['errores'][] = 'Se detuvo la revisión de errores después de 50; corrige el archivo y vuelve a subirlo.';
                break;
            }

            try {
                $resultado = $this->procesarFila($vendor, $fila, $categorias);
                $resumen[$resultado]++;
            } catch (FilaInvalida $error) {
                $resumen['errores'][] = "Fila {$numero}: {$error->getMessage()}";
            } catch (Throwable $error) {
                report($error);
                $resumen['errores'][] = "Fila {$numero}: no se pudo guardar.";
            }
        }

        return $resumen;
    }

    /**
     * Contenido de la plantilla CSV (con BOM para que Excel respete los acentos).
     */
    public static function plantilla(): string
    {
        $lineas = [
            implode(',', self::COLUMNAS),
            'ARROZ-001,Arroz blanco 1 lb,Abarrotes,9.50,,120,libra,7401234567890,Arroz de grano largo,454,si',
            'ACEITE-900,Aceite vegetal 900 ml,Abarrotes,24.65,21.85,40,unidad,,,,si',
        ];

        return "\u{FEFF}".implode("\r\n", $lineas)."\r\n";
    }

    /**
     * @param  Collection<int, Categoria>  $categorias
     * @param  array<string, string>  $fila
     * @return 'creados'|'actualizados'
     */
    private function procesarFila(Vendor $vendor, array $fila, $categorias): string
    {
        $sku = trim($fila['sku'] ?? '');
        if ($sku === '' || mb_strlen($sku) > 80) {
            throw new FilaInvalida('falta el SKU o es demasiado largo.');
        }

        $datos = [];

        if (($fila['nombre'] ?? '') !== '') {
            $datos['nombre'] = mb_substr(trim($fila['nombre']), 0, 180);
        }

        if (($fila['categoria'] ?? '') !== '') {
            $buscada = Str::slug($fila['categoria']);
            $categoria = $categorias->first(fn (Categoria $c): bool => $c->slug === $buscada || Str::slug($c->nombre) === $buscada);
            if ($categoria === null) {
                throw new FilaInvalida("la categoría \"{$fila['categoria']}\" no existe.");
            }
            $datos['categoria_id'] = $categoria->id;
        }

        if (($fila['precio'] ?? '') !== '') {
            $datos['precio_base'] = round($this->numero($fila['precio'], 'precio', 0.01), 2);
        } elseif (($fila['precio_sin_iva'] ?? '') !== '') {
            $datos['precio_base'] = Iva::agregar($this->numero($fila['precio_sin_iva'], 'precio_sin_iva', 0.01));
        }

        if (array_key_exists('oferta', $fila)) {
            $datos['precio_oferta'] = $fila['oferta'] === '' ? null : round($this->numero($fila['oferta'], 'oferta', 0.01), 2);
        } elseif (array_key_exists('oferta_sin_iva', $fila)) {
            $datos['precio_oferta'] = $fila['oferta_sin_iva'] === ''
                ? null
                : Iva::agregar($this->numero($fila['oferta_sin_iva'], 'oferta_sin_iva', 0.01));
        }

        if (($fila['unidad_medida'] ?? '') !== '') {
            $unidad = Str::lower(trim($fila['unidad_medida']));
            if (! in_array($unidad, self::UNIDADES, true)) {
                throw new FilaInvalida('unidad_medida debe ser: '.implode(', ', self::UNIDADES).'.');
            }
            $datos['unidad_medida'] = $unidad;
        }

        foreach (['codigo_barras' => 60, 'descripcion' => 2000] as $campo => $largo) {
            if (($fila[$campo] ?? '') !== '') {
                $datos[$campo] = mb_substr(trim($fila[$campo]), 0, $largo);
            }
        }

        if (($fila['peso_gramos'] ?? '') !== '') {
            $datos['peso_gramos'] = (int) $this->numero($fila['peso_gramos'], 'peso_gramos', 0);
        }

        if (($fila['visible'] ?? '') !== '') {
            $visible = in_array(Str::lower(Str::ascii(trim($fila['visible']))), ['si', 's', '1', 'true', 'x'], true);
            $datos['visible_catalogo'] = $visible;
            $datos['is_active'] = $visible;
        }

        $existencias = ($fila['existencias'] ?? '') !== ''
            ? (int) $this->numero($fila['existencias'], 'existencias', 0)
            : null;

        if (isset($datos['precio_oferta'], $datos['precio_base']) && $datos['precio_oferta'] >= $datos['precio_base']) {
            throw new FilaInvalida('la oferta debe ser menor que el precio.');
        }

        return DB::transaction(function () use ($vendor, $sku, $datos, $existencias): string {
            $producto = Producto::query()->where('vendor_id', $vendor->id)->where('sku', $sku)->lockForUpdate()->first();

            if ($producto === null) {
                try {
                    app(PlanesVendedorService::class)->assertPuedeAgregarProductos($vendor);
                } catch (\RuntimeException $exception) {
                    throw new FilaInvalida($exception->getMessage());
                }

                foreach (['nombre', 'categoria_id', 'precio_base'] as $requerido) {
                    if (! isset($datos[$requerido])) {
                        throw new FilaInvalida('producto nuevo: faltan nombre, categoría o precio.');
                    }
                }

                $visible = $datos['visible_catalogo'] ?? true;
                $producto = Producto::query()->create([
                    'unidad_medida' => 'unidad',
                    'requiere_refrigeracion' => false,
                    ...$datos,
                    'is_active' => $datos['is_active'] ?? true,
                    'visible_catalogo' => $visible,
                    'uuid' => (string) Str::uuid(),
                    'vendor_id' => $vendor->id,
                    'sku' => $sku,
                    'slug' => Str::slug($datos['nombre']).'-'.Str::lower(Str::random(6)),
                    'publicado_at' => $visible ? now() : null,
                ]);
                $producto->inventario()->create([
                    'stock_actual' => $existencias ?? 0,
                    'stock_reservado' => 0,
                    'stock_minimo' => 5,
                    'stock_maximo' => max(100, $existencias ?? 0),
                    'ultima_actualizacion' => now(),
                ]);

                return 'creados';
            }

            if (($datos['visible_catalogo'] ?? false) && $producto->publicado_at === null) {
                $datos['publicado_at'] = now();
            }

            if (isset($datos['precio_base']) && ! array_key_exists('precio_oferta', $datos)
                && $producto->precio_oferta !== null && (float) $producto->precio_oferta >= $datos['precio_base']) {
                $datos['precio_oferta'] = null;
            }

            $producto->update($datos);

            if ($existencias !== null) {
                $producto->inventario()->updateOrCreate([], [
                    'stock_actual' => $existencias,
                    'ultima_actualizacion' => now(),
                ]);
            }

            return 'actualizados';
        });
    }

    private function numero(string $valor, string $campo, float $minimo): float
    {
        $limpio = str_replace([' ', 'Q', 'q'], '', trim($valor));
        /* Acepta "1,250.50" y "1250,50". */
        if (preg_match('/^\d+,\d{1,2}$/', $limpio) === 1) {
            $limpio = str_replace(',', '.', $limpio);
        }
        $limpio = str_replace(',', '', $limpio);

        if (! is_numeric($limpio) || (float) $limpio < $minimo) {
            throw new FilaInvalida("{$campo} no es un número válido.");
        }

        return (float) $limpio;
    }

    /**
     * Lee el CSV detectando separador (coma o punto y coma).
     *
     * @return array<int, array<string, string>>|null Filas indexadas por numero de linea.
     */
    private function leer(string $ruta): ?array
    {
        $contenido = (string) file_get_contents($ruta);
        $contenido = preg_replace('/^\x{FEFF}/u', '', $contenido) ?? $contenido;

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $lineas = preg_split('/\r\n|\n|\r/', trim($contenido)) ?: [];
        if (count($lineas) < 2) {
            return null;
        }

        $separador = substr_count($lineas[0], ';') > substr_count($lineas[0], ',') ? ';' : ',';
        $encabezados = array_map(
            fn (string $columna): string => Str::snake(Str::ascii(trim(Str::lower($columna)))),
            str_getcsv($lineas[0], $separador, '"', '')
        );

        if (! in_array('sku', $encabezados, true)) {
            return null;
        }

        $filas = [];
        foreach (array_slice($lineas, 1, self::MAX_FILAS, true) as $indice => $linea) {
            if (trim($linea) === '' || trim($linea, $separador.' ') === '') {
                continue;
            }
            $valores = str_getcsv($linea, $separador, '"', '');
            $fila = [];
            foreach ($encabezados as $posicion => $columna) {
                if (in_array($columna, [...self::COLUMNAS, 'precio_sin_iva', 'oferta_sin_iva'], true)) {
                    $fila[$columna] = trim((string) ($valores[$posicion] ?? ''));
                }
            }
            $filas[$indice + 1] = $fila;
        }

        return $filas;
    }
}
