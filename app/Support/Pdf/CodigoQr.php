<?php

namespace App\Support\Pdf;

use InvalidArgumentException;

/**
 * Generador de códigos QR (modo byte, corrección de errores nivel M, versiones 1 a 10).
 * Alcanza para enlaces de hasta 213 caracteres. Sin dependencias externas.
 * Basado en el estándar ISO/IEC 18004.
 */
class CodigoQr
{
    /** Por versión: [codewords de datos por bloque del grupo 1, bloques g1, datos g2, bloques g2, codewords de corrección por bloque]. */
    private const BLOQUES_M = [
        1 => [16, 1, 0, 0, 10],
        2 => [28, 1, 0, 0, 16],
        3 => [44, 1, 0, 0, 26],
        4 => [32, 2, 0, 0, 18],
        5 => [43, 2, 0, 0, 24],
        6 => [27, 4, 0, 0, 16],
        7 => [31, 4, 0, 0, 18],
        8 => [38, 2, 39, 2, 22],
        9 => [36, 3, 37, 2, 22],
        10 => [43, 4, 44, 1, 26],
    ];

    private const ALINEACION = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];

    /** @var array<int, array<int, bool>> */
    private array $modulos = [];

    /** @var array<int, array<int, bool>> */
    private array $esFuncion = [];

    private int $tamano = 0;

    /**
     * Matriz del QR: true = módulo oscuro. Índices [fila][columna].
     *
     * @return array<int, array<int, bool>>
     */
    public static function matriz(string $texto, ?int $mascaraFija = null): array
    {
        return (new self)->generar($texto, $mascaraFija);
    }

    /**
     * @return array<int, array<int, bool>>
     */
    private function generar(string $texto, ?int $mascaraFija = null): array
    {
        $bytes = array_values(unpack('C*', $texto) ?: []);
        $version = $this->elegirVersion(count($bytes));
        $this->tamano = 17 + 4 * $version;
        $this->modulos = array_fill(0, $this->tamano, array_fill(0, $this->tamano, false));
        $this->esFuncion = $this->modulos;

        $this->dibujarPatrones($version);
        $codewords = $this->agregarCorreccion($this->codificar($bytes, $version), $version);
        $this->colocarDatos($codewords);

        /* Se prueba cada máscara y se queda la de menor penalización. */
        $mejor = $mascaraFija ?? 0;
        $menor = PHP_INT_MAX;
        for ($mascara = 0; $mascara < 8 && $mascaraFija === null; $mascara++) {
            $this->aplicarMascara($mascara);
            $this->dibujarFormato($mascara);
            $penal = $this->penalizacion();
            if ($penal < $menor) {
                $menor = $penal;
                $mejor = $mascara;
            }
            $this->aplicarMascara($mascara);
        }
        $this->aplicarMascara($mejor);
        $this->dibujarFormato($mejor);

        return $this->modulos;
    }

    private function elegirVersion(int $largo): int
    {
        foreach (self::BLOQUES_M as $version => [$d1, $b1, $d2, $b2]) {
            $capacidadBits = ($d1 * $b1 + $d2 * $b2) * 8;
            $bitsConteo = $version < 10 ? 8 : 16;
            if (4 + $bitsConteo + 8 * $largo <= $capacidadBits) {
                return $version;
            }
        }

        throw new InvalidArgumentException('El texto es demasiado largo para el código QR.');
    }

    /**
     * @param  array<int, int>  $bytes
     * @return array<int, int>
     */
    private function codificar(array $bytes, int $version): array
    {
        [$d1, $b1, $d2, $b2] = self::BLOQUES_M[$version];
        $capacidad = $d1 * $b1 + $d2 * $b2;

        $bits = '0100'.str_pad(decbin(count($bytes)), $version < 10 ? 8 : 16, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        $bits .= str_repeat('0', min(4, $capacidad * 8 - strlen($bits)));
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);

        $datos = array_map('bindec', str_split($bits, 8));
        for ($relleno = 0xEC; count($datos) < $capacidad; $relleno ^= 0xEC ^ 0x11) {
            $datos[] = $relleno;
        }

        return $datos;
    }

    /**
     * Divide en bloques, calcula Reed-Solomon y entrelaza.
     *
     * @param  array<int, int>  $datos
     * @return array<int, int>
     */
    private function agregarCorreccion(array $datos, int $version): array
    {
        [$d1, $b1, $d2, $b2, $ec] = self::BLOQUES_M[$version];
        $divisor = $this->divisorRs($ec);
        $bloques = [];
        $pos = 0;
        foreach ([[$d1, $b1], [$d2, $b2]] as [$largo, $cantidad]) {
            for ($i = 0; $i < $cantidad; $i++) {
                $bloque = array_slice($datos, $pos, $largo);
                $pos += $largo;
                $bloques[] = [$bloque, $this->residuoRs($bloque, $divisor)];
            }
        }

        $resultado = [];
        $maxDatos = max($d1, $d2);
        for ($i = 0; $i < $maxDatos; $i++) {
            foreach ($bloques as [$bloque]) {
                if ($i < count($bloque)) {
                    $resultado[] = $bloque[$i];
                }
            }
        }
        for ($i = 0; $i < $ec; $i++) {
            foreach ($bloques as [, $correccion]) {
                $resultado[] = $correccion[$i];
            }
        }

        return $resultado;
    }

    /**
     * @return array<int, int>
     */
    private function divisorRs(int $grado): array
    {
        $resultado = array_fill(0, $grado, 0);
        $resultado[$grado - 1] = 1;
        $raiz = 1;
        for ($i = 0; $i < $grado; $i++) {
            for ($j = 0; $j < $grado; $j++) {
                $resultado[$j] = $this->multiplicar($resultado[$j], $raiz);
                if ($j + 1 < $grado) {
                    $resultado[$j] ^= $resultado[$j + 1];
                }
            }
            $raiz = $this->multiplicar($raiz, 0x02);
        }

        return $resultado;
    }

    /**
     * @param  array<int, int>  $datos
     * @param  array<int, int>  $divisor
     * @return array<int, int>
     */
    private function residuoRs(array $datos, array $divisor): array
    {
        $resultado = array_fill(0, count($divisor), 0);
        foreach ($datos as $b) {
            $factor = $b ^ array_shift($resultado);
            $resultado[] = 0;
            foreach ($divisor as $i => $coef) {
                $resultado[$i] ^= $this->multiplicar($coef, $factor);
            }
        }

        return $resultado;
    }

    private function multiplicar(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z & 0xFF;
    }

    private function poner(int $x, int $y, bool $oscuro): void
    {
        $this->modulos[$y][$x] = $oscuro;
        $this->esFuncion[$y][$x] = true;
    }

    private function dibujarPatrones(int $version): void
    {
        $n = $this->tamano;
        for ($i = 0; $i < $n; $i++) {
            $this->poner(6, $i, $i % 2 === 0);
            $this->poner($i, 6, $i % 2 === 0);
        }

        foreach ([[3, 3], [$n - 4, 3], [3, $n - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $x = $cx + $dx;
                    $y = $cy + $dy;
                    if ($x >= 0 && $x < $n && $y >= 0 && $y < $n) {
                        $d = max(abs($dx), abs($dy));
                        $this->poner($x, $y, $d !== 2 && $d !== 4);
                    }
                }
            }
        }

        $pos = self::ALINEACION[$version];
        $ultimo = count($pos) - 1;
        foreach ($pos as $i => $px) {
            foreach ($pos as $j => $py) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $ultimo) || ($i === $ultimo && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->poner($px + $dx, $py + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }

        /* Reserva el área de formato (se dibuja de verdad al elegir la máscara). */
        $this->dibujarFormato(0);

        if ($version >= 7) {
            $resto = $version;
            for ($i = 0; $i < 12; $i++) {
                $resto = ($resto << 1) ^ (($resto >> 11) * 0x1F25);
            }
            $bits = ($version << 12) | $resto;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($bits >> $i) & 1) === 1;
                $a = $n - 11 + $i % 3;
                $b = intdiv($i, 3);
                $this->poner($a, $b, $bit);
                $this->poner($b, $a, $bit);
            }
        }
    }

    private function dibujarFormato(int $mascara): void
    {
        $n = $this->tamano;
        $datos = (0 << 3) | $mascara; /* Nivel M = 00 */
        $resto = $datos;
        for ($i = 0; $i < 10; $i++) {
            $resto = ($resto << 1) ^ (($resto >> 9) * 0x537);
        }
        $bits = (($datos << 10) | $resto) ^ 0x5412;
        $bit = fn (int $i): bool => (($bits >> $i) & 1) === 1;

        for ($i = 0; $i <= 5; $i++) {
            $this->poner(8, $i, $bit($i));
        }
        $this->poner(8, 7, $bit(6));
        $this->poner(8, 8, $bit(7));
        $this->poner(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->poner(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->poner($n - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->poner(8, $n - 15 + $i, $bit($i));
        }
        $this->poner(8, $n - 8, true);
    }

    /**
     * @param  array<int, int>  $codewords
     */
    private function colocarDatos(array $codewords): void
    {
        $n = $this->tamano;
        $totalBits = count($codewords) * 8;
        $i = 0;
        for ($derecha = $n - 1; $derecha >= 1; $derecha -= 2) {
            if ($derecha === 6) {
                $derecha = 5;
            }
            for ($vert = 0; $vert < $n; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $derecha - $j;
                    $haciaArriba = (($derecha + 1) & 2) === 0;
                    $y = $haciaArriba ? $n - 1 - $vert : $vert;
                    if (! $this->esFuncion[$y][$x] && $i < $totalBits) {
                        $this->modulos[$y][$x] = (($codewords[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                        $i++;
                    }
                }
            }
        }
    }

    private function aplicarMascara(int $mascara): void
    {
        $n = $this->tamano;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($this->esFuncion[$y][$x]) {
                    continue;
                }
                $invertir = match ($mascara) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => ($x * $y) % 2 + ($x * $y) % 3 === 0,
                    6 => (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0,
                    default => ((($x + $y) % 2) + ($x * $y) % 3) % 2 === 0,
                };
                if ($invertir) {
                    $this->modulos[$y][$x] = ! $this->modulos[$y][$x];
                }
            }
        }
    }

    /**
     * Penalización simplificada (filas/columnas iguales, bloques 2x2 y proporción de oscuros).
     */
    private function penalizacion(): int
    {
        $n = $this->tamano;
        $m = $this->modulos;
        $penal = 0;
        $oscuros = 0;
        for ($a = 0; $a < $n; $a++) {
            $rachaF = $rachaC = 1;
            for ($b = 0; $b < $n; $b++) {
                $oscuros += $m[$a][$b] ? 1 : 0;
                if ($b > 0) {
                    $rachaF = $m[$a][$b] === $m[$a][$b - 1] ? $rachaF + 1 : 1;
                    $rachaC = $m[$b][$a] === $m[$b - 1][$a] ? $rachaC + 1 : 1;
                    $penal += ($rachaF === 5 ? 3 : ($rachaF > 5 ? 1 : 0)) + ($rachaC === 5 ? 3 : ($rachaC > 5 ? 1 : 0));
                }
                if ($a > 0 && $b > 0 && $m[$a][$b] === $m[$a - 1][$b] && $m[$a][$b] === $m[$a][$b - 1] && $m[$a][$b] === $m[$a - 1][$b - 1]) {
                    $penal += 3;
                }
            }
        }

        return $penal + intdiv(abs($oscuros * 20 - $n * $n * 10), $n * $n) * 10;
    }
}
