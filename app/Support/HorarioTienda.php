<?php

namespace App\Support;

use App\Models\Vendor;
use Illuminate\Support\Carbon;

/**
 * Horario de atencion y pausa de una tienda.
 *
 * Si la tienda no define horario se usa el general de Atlantia (config atlantia.horario).
 * Fuera de horario solo se aceptan entregas programadas; en pausa no se aceptan pedidos.
 */
class HorarioTienda
{
    /**
     * @return array{abierto: bool, pausado: bool, recibe_programados: bool, apertura: string, cierre: string, mensaje: ?string}
     */
    public static function estado(?Vendor $vendor, ?Carbon $ahora = null): array
    {
        $ahora = ($ahora ?? now())->copy()->setTimezone(config('app.timezone'));
        $apertura = self::hora($vendor?->hora_apertura, (string) config('atlantia.horario.apertura', '07:00'));
        $cierre = self::hora($vendor?->hora_cierre, (string) config('atlantia.horario.cierre', '21:00'));

        $pausadoHasta = $vendor?->pausado_hasta;
        if ($pausadoHasta !== null && $pausadoHasta->isFuture()) {
            return [
                'abierto' => false,
                'pausado' => true,
                'recibe_programados' => false,
                'apertura' => $apertura,
                'cierre' => $cierre,
                'mensaje' => 'Pausado por alta demanda. Volvemos a recibir pedidos '
                    .($pausadoHasta->isSameDay($ahora) ? 'a las ' : 'el '.$pausadoHasta->translatedFormat('l').' a las ')
                    .self::legible($pausadoHasta->format('H:i')),
            ];
        }

        $abre = $ahora->copy()->setTimeFromTimeString($apertura);
        $cierra = $ahora->copy()->setTimeFromTimeString($cierre);
        $abierto = $ahora->betweenIncluded($abre, $cierra->copy()->subSecond());

        return [
            'abierto' => $abierto,
            'pausado' => false,
            'recibe_programados' => true,
            'apertura' => $apertura,
            'cierre' => $cierre,
            'mensaje' => $abierto
                ? null
                : ($ahora->lt($abre)
                    ? 'Cerrado, abrimos a las '.self::legible($apertura)
                    : 'Cerrado, abrimos mañana a las '.self::legible($apertura)),
        ];
    }

    /**
     * Estado de la tienda propia de Atlantia (para el aviso general del sitio).
     *
     * @return array{abierto: bool, pausado: bool, recibe_programados: bool, apertura: string, cierre: string, mensaje: ?string}
     */
    public static function estadoAtlantia(): array
    {
        $vendor = Vendor::query()->where('slug', config('atlantia.vendor_propio_slug', 'atlantia-supermarket'))->first();

        return self::estado($vendor);
    }

    /**
     * Convierte "07:00" en "7:00 a. m." (ya termina en punto).
     */
    public static function legible(string $hora): string
    {
        $momento = Carbon::createFromFormat('H:i', substr($hora, 0, 5));

        return $momento->format('g:i').($momento->hour < 12 ? ' a. m.' : ' p. m.');
    }

    private static function hora(mixed $valor, string $porDefecto): string
    {
        $texto = trim((string) $valor);

        return preg_match('/^\d{2}:\d{2}/', $texto) === 1 ? substr($texto, 0, 5) : $porDefecto;
    }
}
