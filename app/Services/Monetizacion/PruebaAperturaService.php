<?php

namespace App\Services\Monetizacion;

use App\Models\ServicioContratado;
use App\Models\Vendor;
use App\Notifications\PruebaPlanNotification;
use App\Support\TiendaAtlantia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Promoción de apertura: cada tienda usa cualquier plan gratis unos días (30 por defecto), una sola vez.
 *
 * - Planes con mensualidad: los usa completos sin pagar; al terminar, si no paga, pasa al plan Inicial
 *   sin cerrar la tienda (lo hace el vencimiento normal de planes).
 * - Plan Inicial: esos días no se cobra comisión por venta.
 */
class PruebaAperturaService
{
    public const DIAS_AVISO = 5;

    /**
     * La promoción está activa y no ha pasado su fecha límite.
     */
    public static function vigente(?Carbon $hoy = null): bool
    {
        $config = (array) config('monetizacion.apertura', []);
        if (! (bool) ($config['activa'] ?? false) || self::dias() < 1) {
            return false;
        }

        $hasta = $config['hasta'] ?? null;

        return $hasta === null || $hasta === '' || ($hoy ?? now())->startOfDay()->lte(Carbon::parse($hasta)->endOfDay());
    }

    public static function dias(): int
    {
        return (int) config('monetizacion.apertura.dias', 30);
    }

    public static function fechaLimite(): ?Carbon
    {
        $hasta = config('monetizacion.apertura.hasta');

        return filled($hasta) ? Carbon::parse($hasta) : null;
    }

    /**
     * La tienda puede empezar su prueba: promoción vigente, nunca la usó y no tiene un plan pagado corriendo.
     */
    public function disponiblePara(Vendor $vendor): bool
    {
        if (! self::vigente() || $vendor->prueba_usada_at !== null || TiendaAtlantia::es($vendor)) {
            return false;
        }

        $planPagadoVigente = PlanesVendedorService::tieneMensualidad((string) $vendor->plan)
            && $vendor->plan_hasta !== null && $vendor->plan_hasta->isFuture();

        return ! $planPagadoVigente;
    }

    /**
     * Empieza la prueba gratis con el plan elegido.
     */
    public function iniciar(Vendor $vendor, string $plan): Vendor
    {
        $contratables = PlanesVendedorService::contratables();
        if (! isset($contratables[$plan])) {
            throw new RuntimeException('Ese plan no se puede probar gratis.');
        }

        return DB::transaction(function () use ($vendor, $plan, $contratables): Vendor {
            $vendor = Vendor::query()->whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            if (! $this->disponiblePara($vendor)) {
                throw new RuntimeException('Esta tienda ya usó su prueba gratis o la promoción terminó.');
            }

            $datos = $contratables[$plan];
            $hasta = now()->addDays(self::dias());
            $conMensualidad = PlanesVendedorService::tieneMensualidad($plan);

            $vendor->forceFill([
                'plan' => $plan,
                'plan_hasta' => $conMensualidad ? $hasta : null,
                'commission_percentage' => $datos['comision'],
                'monthly_rent' => (float) ($datos['precio'] ?? 0),
                'limite_productos' => null,
                'prueba_plan' => $plan,
                'prueba_hasta' => $hasta,
                'prueba_usada_at' => now(),
                'prueba_aviso_previo_at' => null,
                'prueba_aviso_fin_at' => null,
            ])->save();

            return $vendor->refresh();
        });
    }

    /**
     * Avisos en el panel: unos días antes de terminar y el día que termina. Devuelve cuántos envió.
     */
    public function avisar(): int
    {
        $enviados = 0;

        Vendor::query()->whereNotNull('prueba_hasta')->whereNull('prueba_aviso_previo_at')
            ->whereBetween('prueba_hasta', [now(), now()->addDays(self::DIAS_AVISO)])
            ->with('user')
            ->each(function (Vendor $vendor) use (&$enviados): void {
                $vendor->forceFill(['prueba_aviso_previo_at' => now()])->save();
                $enviados += $this->notificar($vendor, 'por_terminar');
            });

        Vendor::query()->whereNotNull('prueba_hasta')->whereNull('prueba_aviso_fin_at')
            ->where('prueba_hasta', '<=', now())
            ->with('user')
            ->each(function (Vendor $vendor) use (&$enviados): void {
                $vendor->forceFill(['prueba_aviso_fin_at' => now(), 'prueba_aviso_previo_at' => $vendor->prueba_aviso_previo_at ?? now()])->save();
                $enviados += $this->notificar($vendor, 'terminada');
            });

        return $enviados;
    }

    /**
     * La tienda pagó su plan después de empezar la prueba.
     */
    public static function pagoDespues(Vendor $vendor): bool
    {
        return $vendor->prueba_usada_at !== null && ServicioContratado::query()->where('vendor_id', $vendor->id)
            ->where('tipo', 'plan')->whereNotNull('pagado_at')->where('monto', '>', 0)
            ->where('pagado_at', '>=', $vendor->prueba_usada_at)->exists();
    }

    private function notificar(Vendor $vendor, string $momento): int
    {
        if ($vendor->user === null) {
            return 0;
        }

        try {
            $vendor->user->notify(new PruebaPlanNotification($vendor, $momento));

            return 1;
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
