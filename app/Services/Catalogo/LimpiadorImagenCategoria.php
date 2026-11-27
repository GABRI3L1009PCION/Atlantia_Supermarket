<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Prepara la imagen de una categoria para la tarjeta del catalogo: la reduce a un tamano
 * ligero y vuelve transparente el fondo claro y parejo (blanco o gris), para que no se vea
 * un recuadro sobre la tarjeta blanca.
 */
class LimpiadorImagenCategoria
{
    private const LADO_MAXIMO = 640;

    /** Diferencia de color con el fondo a partir de la cual el pixel empieza a ser visible. */
    private const UMBRAL_INICIO = 10;

    /** Diferencia a partir de la cual el pixel es totalmente opaco. */
    private const UMBRAL_OPACO = 42;

    /**
     * Procesa un archivo del disco publico y devuelve la ruta de la version limpia (.webp).
     * Si el archivo no se puede leer, devuelve la ruta original sin tocarla.
     */
    public function procesar(string $ruta, bool $borrarOriginal = false): string
    {
        $disco = Storage::disk('public');

        if (! $disco->exists($ruta) || ! function_exists('imagecreatefromstring')) {
            return $ruta;
        }

        $origen = @imagecreatefromstring((string) $disco->get($ruta));
        if ($origen === false) {
            return $ruta;
        }

        $imagen = $this->reducir($origen);
        imagedestroy($origen);

        $fondo = $this->colorDeFondo($imagen);
        if ($fondo !== null) {
            $this->quitarFondo($imagen, $fondo);
        }

        ob_start();
        imagewebp($imagen, null, 86);
        $contenido = (string) ob_get_clean();
        imagedestroy($imagen);

        $nueva = 'categorias/'.Str::random(40).'.webp';
        $disco->put($nueva, $contenido);

        if ($borrarOriginal && $nueva !== $ruta) {
            $disco->delete($ruta);
        }

        return $nueva;
    }

    private function reducir(\GdImage $origen): \GdImage
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $escala = min(1, self::LADO_MAXIMO / max($ancho, $alto));
        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto = max(1, (int) round($alto * $escala));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        return $destino;
    }

    /**
     * Promedio de las cuatro esquinas. Solo se usa como fondo si es claro, opaco y parejo.
     *
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function colorDeFondo(\GdImage $imagen): ?array
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $muestras = [];

        foreach ([[0, 0], [$ancho - 6, 0], [0, $alto - 6], [$ancho - 6, $alto - 6]] as [$x0, $y0]) {
            for ($x = $x0; $x < $x0 + 6; $x++) {
                for ($y = $y0; $y < $y0 + 6; $y++) {
                    $c = imagecolorat($imagen, max(0, $x), max(0, $y));
                    if ((($c >> 24) & 0x7F) > 10) {
                        return null;
                    }
                    $muestras[] = [($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF];
                }
            }
        }

        $promedio = [0, 0, 0];
        foreach ($muestras as $m) {
            $promedio[0] += $m[0];
            $promedio[1] += $m[1];
            $promedio[2] += $m[2];
        }
        $promedio = array_map(fn (int $v): int => (int) round($v / count($muestras)), $promedio);

        if (min($promedio) < 200) {
            return null;
        }

        foreach ($muestras as $m) {
            if (max(abs($m[0] - $promedio[0]), abs($m[1] - $promedio[1]), abs($m[2] - $promedio[2])) > 14) {
                return null;
            }
        }

        return $promedio;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $fondo
     */
    private function quitarFondo(\GdImage $imagen, array $fondo): void
    {
        [$br, $bg, $bb] = $fondo;
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $rango = self::UMBRAL_OPACO - self::UMBRAL_INICIO;

        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        for ($y = 0; $y < $alto; $y++) {
            for ($x = 0; $x < $ancho; $x++) {
                $c = imagecolorat($imagen, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $distancia = max(abs($r - $br), abs($g - $bg), abs($b - $bb));

                if ($distancia >= self::UMBRAL_OPACO) {
                    continue;
                }

                $a = max(0.0, ($distancia - self::UMBRAL_INICIO) / $rango);

                if ($a <= 0.0) {
                    imagesetpixel($imagen, $x, $y, imagecolorallocatealpha($imagen, $br, $bg, $bb, 127));

                    continue;
                }

                /* Separa el color del fondo para que el borde no quede con un halo gris. */
                $nr = (int) max(0, min(255, round(($r - (1 - $a) * $br) / $a)));
                $ng = (int) max(0, min(255, round(($g - (1 - $a) * $bg) / $a)));
                $nb = (int) max(0, min(255, round(($b - (1 - $a) * $bb) / $a)));
                imagesetpixel($imagen, $x, $y, imagecolorallocatealpha($imagen, $nr, $ng, $nb, (int) round((1 - $a) * 127)));
            }
        }
    }
}
