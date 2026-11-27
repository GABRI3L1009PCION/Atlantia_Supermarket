<?php

namespace App\Support\Pdf;

use GdImage;

/**
 * Generador de PDF sencillo y sin dependencias para comprobantes: textos con acentos
 * (Helvetica con codificación WinAnsi), rectángulos, líneas, imágenes (logo) y varias páginas.
 * Coordenadas en puntos; el origen (0,0) es la esquina inferior izquierda de una hoja carta.
 */
class DocumentoPdf
{
    public const ANCHO = 612;

    public const ALTO = 792;

    /** @var array<int, array<int, string>> */
    private array $paginas = [[]];

    /** @var array<string, array{datos: string, ancho: int, alto: int}> */
    private array $imagenes = [];

    public function nuevaPagina(): void
    {
        $this->paginas[] = [];
    }

    public function color(float $r, float $g, float $b): self
    {
        return $this->op(sprintf('%.3F %.3F %.3F rg', $r, $g, $b));
    }

    public function colorLinea(float $r, float $g, float $b): self
    {
        return $this->op(sprintf('%.3F %.3F %.3F RG', $r, $g, $b));
    }

    public function texto(float $x, float $y, float $tamano, string $texto, bool $negrita = false): self
    {
        return $this->op(sprintf('BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET', $negrita ? 'F2' : 'F1', $tamano, $x, $y, $this->escapar($texto)));
    }

    /**
     * Texto alineado a la derecha terminando en $xDerecha (ancho aproximado de Helvetica).
     */
    public function textoDerecha(float $xDerecha, float $y, float $tamano, string $texto, bool $negrita = false): self
    {
        return $this->texto($xDerecha - $this->anchoTexto($texto, $tamano, $negrita), $y, $tamano, $texto, $negrita);
    }

    public function textoCentrado(float $xCentro, float $y, float $tamano, string $texto, bool $negrita = false): self
    {
        return $this->texto($xCentro - $this->anchoTexto($texto, $tamano, $negrita) / 2, $y, $tamano, $texto, $negrita);
    }

    public function rect(float $x, float $y, float $ancho, float $alto, string $modo = 'f'): self
    {
        return $this->op(sprintf('%.2F %.2F %.2F %.2F re %s', $x, $y, $ancho, $alto, $modo));
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, float $grosor = 0.6): self
    {
        return $this->op(sprintf('%.2F w %.2F %.2F m %.2F %.2F l S', $grosor, $x1, $y1, $x2, $y2));
    }

    /**
     * Dibuja una imagen (JPG, PNG o WebP) dentro de la caja indicada, sin deformarla.
     * Si la imagen no se puede leer, no dibuja nada y devuelve false.
     */
    public function imagen(string $ruta, float $x, float $y, float $anchoMax, float $altoMax): bool
    {
        $jpeg = self::jpegDesde($ruta);
        if ($jpeg === null) {
            return false;
        }

        $nombre = 'Im'.(count($this->imagenes) + 1);
        $this->imagenes[$nombre] = $jpeg;

        $escala = min($anchoMax / $jpeg['ancho'], $altoMax / $jpeg['alto']);
        $ancho = $jpeg['ancho'] * $escala;
        $alto = $jpeg['alto'] * $escala;
        /* Centrada verticalmente en la caja, alineada a la izquierda. */
        $this->op(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q', $ancho, $alto, $x, $y + ($altoMax - $alto) / 2, $nombre));

        return true;
    }

    /**
     * Dibuja un código QR cuadrado (con su margen blanco) en la posición indicada.
     */
    public function qr(string $texto, float $x, float $y, float $lado): self
    {
        $matriz = CodigoQr::matriz($texto);
        $n = count($matriz);
        $modulo = $lado / ($n + 4);
        $this->color(1, 1, 1)->rect($x, $y, $lado, $lado);
        $this->color(0, 0, 0);
        foreach ($matriz as $fila => $columnas) {
            foreach ($columnas as $col => $oscuro) {
                if ($oscuro) {
                    $this->rect($x + ($col + 2) * $modulo, $y + $lado - ($fila + 3) * $modulo, $modulo + 0.05, $modulo + 0.05);
                }
            }
        }

        return $this;
    }

    /**
     * Corta el texto en líneas que caben en el ancho dado.
     *
     * @return array<int, string>
     */
    public function partir(string $texto, float $anchoMax, float $tamano, bool $negrita = false): array
    {
        $lineas = [];
        $actual = '';
        foreach (preg_split('/\s+/u', trim($texto)) ?: [] as $palabra) {
            $prueba = $actual === '' ? $palabra : $actual.' '.$palabra;
            if ($this->anchoTexto($prueba, $tamano, $negrita) > $anchoMax && $actual !== '') {
                $lineas[] = $actual;
                $actual = $palabra;
            } else {
                $actual = $prueba;
            }
        }
        if ($actual !== '') {
            $lineas[] = $actual;
        }

        return $lineas ?: [''];
    }

    public function anchoTexto(string $texto, float $tamano, bool $negrita = false): float
    {
        /* Promedio de ancho de Helvetica por carácter (suficiente para alinear importes y títulos). */
        $factor = $negrita ? 0.56 : 0.52;
        $ancho = 0.0;
        foreach (mb_str_split($texto) as $c) {
            $ancho += match (true) {
                $c === ' ' => 0.28,
                in_array($c, ['i', 'l', 'j', 't', 'f', 'I', '.', ',', ':', ';', '|', '!', '\''], true) => 0.28,
                in_array($c, ['m', 'w', 'M', 'W'], true) => 0.83,
                ctype_upper($c) || ctype_digit($c) => $factor + 0.1,
                default => $factor,
            };
        }

        return $ancho * $tamano;
    }

    public function salida(): string
    {
        $objetos = [];
        $nPaginas = count($this->paginas);
        /* 1 catálogo, 2 páginas, 3-4 fuentes, 5..: imágenes, luego (página + contenido) por hoja. */
        $idImagen = [];
        $siguiente = 5;
        foreach ($this->imagenes as $nombre => $_) {
            $idImagen[$nombre] = $siguiente++;
        }
        $idsPaginas = [];
        for ($i = 0; $i < $nPaginas; $i++) {
            $idsPaginas[] = [$siguiente, $siguiente + 1];
            $siguiente += 2;
        }

        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($p) => $p[0].' 0 R', $idsPaginas)).'] /Count '.$nPaginas.' >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->imagenes as $nombre => $img) {
            $objetos[$idImagen[$nombre]] = '<< /Type /XObject /Subtype /Image /Width '.$img['ancho'].' /Height '.$img['alto']
                .' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($img['datos'])." >>\nstream\n".$img['datos']."\nendstream";
        }

        $recursosImg = $this->imagenes === [] ? '' : ' /XObject << '.implode(' ', array_map(fn ($n) => '/'.$n.' '.$idImagen[$n].' 0 R', array_keys($this->imagenes))).' >>';
        foreach ($this->paginas as $i => $ops) {
            [$idPagina, $idContenido] = $idsPaginas[$i];
            $contenido = implode("\n", $ops);
            $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::ANCHO.' '.self::ALTO.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'.$recursosImg.' >> /Contents '.$idContenido.' 0 R >>';
            $objetos[$idContenido] = '<< /Length '.strlen($contenido)." >>\nstream\n".$contenido."\nendstream";
        }

        ksort($objetos);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objetos as $id => $cuerpo) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$cuerpo."\nendobj\n";
        }

        $xref = strlen($pdf);
        $total = max(array_keys($objetos)) + 1;
        $pdf .= "xref\n0 {$total}\n0000000000 65535 f \n";
        for ($i = 1; $i < $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        return $pdf."trailer\n<< /Size {$total} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function op(string $operacion): self
    {
        $this->paginas[array_key_last($this->paginas)][] = $operacion;

        return $this;
    }

    private function escapar(string $texto): string
    {
        $texto = str_replace(['“', '”', '‘', '’', '–', '—', '·', '•'], ['"', '"', "'", "'", '-', '-', chr(183), chr(149)], $texto);
        $convertido = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        $convertido = $convertido === false ? preg_replace('/[^\x20-\x7E]/', '', $texto) : $convertido;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], (string) $convertido);
    }

    /**
     * @return array{datos: string, ancho: int, alto: int}|null
     */
    public static function jpegDesde(?string $ruta): ?array
    {
        if ($ruta === null || ! is_file($ruta) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $original = @imagecreatefromstring((string) file_get_contents($ruta));
        if (! $original instanceof GdImage) {
            return null;
        }

        /* Fondo blanco (los logos PNG/WebP suelen ser transparentes) y tamaño razonable para el PDF. */
        $ancho = imagesx($original);
        $alto = imagesy($original);
        $escala = min(1, 600 / max($ancho, $alto));
        $w = max(1, (int) round($ancho * $escala));
        $h = max(1, (int) round($alto * $escala));
        $lienzo = imagecreatetruecolor($w, $h);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $original, 0, 0, 0, 0, $w, $h, $ancho, $alto);

        ob_start();
        imagejpeg($lienzo, null, 88);
        $datos = (string) ob_get_clean();
        imagedestroy($original);
        imagedestroy($lienzo);

        return ['datos' => $datos, 'ancho' => $w, 'alto' => $h];
    }
}
