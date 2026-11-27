<?php

namespace App\Services\Monetizacion;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\ServicioContratado;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Planes mensuales para vendedores (un local digital por tienda) y sus limites.
 */
class PlanesVendedorService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function todos(): array
    {
        return (array) config('monetizacion.planes', []);
    }

    /**
     * Planes que el vendedor puede contratar solo (el Personalizado se cotiza).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function contratables(): array
    {
        return array_filter(self::todos(), fn (array $plan): bool => ! ($plan['cotizacion'] ?? false));
    }

    /**
     * @return array<string, mixed>
     */
    public static function datos(string $plan): array
    {
        return self::todos()[$plan] ?? self::todos()[self::base()];
    }

    public static function base(): string
    {
        return (string) config('monetizacion.plan_base', 'inicial');
    }

    /**
     * Un plan con mensualidad (o personalizado) solo vale si esta pagado.
     */
    public static function tieneMensualidad(string $plan): bool
    {
        $precio = self::datos($plan)['precio'] ?? null;

        return $precio === null || (float) $precio > 0;
    }

    /**
     * Plan vigente: sin pagar la mensualidad, o con ella vencida (3 dias de gracia),
     * la tienda opera con el plan Inicial.
     */
    public function planActual(Vendor $vendor): string
    {
        /* La tienda propia de Atlantia opera como Corporativo. */
        if ($vendor->slug === config('atlantia.vendor_propio_slug', 'atlantia-supermarket')) {
            return 'corporativo';
        }

        $plan = (string) ($vendor->plan ?: self::base());
        if (! array_key_exists($plan, self::todos())) {
            return self::base();
        }

        if (self::tieneMensualidad($plan) && ($vendor->plan_hasta === null || Carbon::parse($vendor->plan_hasta)->addDays(3)->isPast())) {
            return self::base();
        }

        return $plan;
    }

    /**
     * Activa o renueva un plan por un mes. Para el Personalizado se indican precio, comision y limite.
     */
    public function aplicar(
        Vendor $vendor,
        string $plan,
        ?string $suscripcionId = null,
        ?float $precio = null,
        ?float $comision = null,
        ?int $limiteProductos = null,
    ): void {
        $datos = self::datos($plan);
        $hasta = $vendor->plan === $plan && $vendor->plan_hasta !== null && Carbon::parse($vendor->plan_hasta)->isFuture()
            ? Carbon::parse($vendor->plan_hasta)->addMonthNoOverflow()
            : now()->addMonthNoOverflow();
        /* Si paga durante su prueba gratis, el mes pagado empieza cuando termina la prueba. */
        if ($vendor->enPrueba() && $vendor->prueba_hasta->gt(now()) && ! ($vendor->plan === $plan && $vendor->plan_hasta !== null && Carbon::parse($vendor->plan_hasta)->isFuture())) {
            $hasta = $vendor->prueba_hasta->copy()->addMonthNoOverflow();
        }

        $vendor->forceFill([
            'plan' => $plan,
            'plan_hasta' => self::tieneMensualidad($plan) ? $hasta : null,
            'plan_suscripcion_id' => $suscripcionId ?? $vendor->plan_suscripcion_id,
            'commission_percentage' => $comision ?? $datos['comision'],
            'monthly_rent' => $precio ?? (float) ($datos['precio'] ?? $vendor->monthly_rent ?? 0),
            'limite_productos' => $plan === 'personalizado' ? ($limiteProductos ?? $vendor->limite_productos) : null,
        ])->save();
    }

    /**
     * Pasa al plan Inicial las tiendas cuya mensualidad no se renovo.
     */
    public function bajarVencidos(): int
    {
        $total = 0;
        Vendor::query()->where('plan', '!=', self::base())->whereNotNull('plan_hasta')
            ->where('plan_hasta', '<', now()->subDays(3))
            ->each(function (Vendor $vendor) use (&$total): void {
                $this->aplicar($vendor, self::base());
                $vendor->forceFill(['plan_suscripcion_id' => null])->save();
                $total++;
            });

        return $total;
    }

    /**
     * Limite de productos del plan vigente (el Personalizado usa el acordado con la tienda).
     */
    public function limiteProductos(Vendor $vendor): int
    {
        $plan = $this->planActual($vendor);

        if ($plan === 'personalizado') {
            return max(1, (int) ($vendor->limite_productos ?? self::datos('corporativo')['max_productos']));
        }

        return (int) self::datos($plan)['max_productos'];
    }

    public function assertPuedeAgregarProductos(Vendor $vendor, int $nuevos = 1): void
    {
        $limite = $this->limiteProductos($vendor);
        $actuales = Producto::query()->where('vendor_id', $vendor->id)->count();

        if ($actuales + $nuevos > $limite) {
            throw new RuntimeException('Tu plan permite hasta '.number_format($limite).' productos. Cambia de plan para agregar más.');
        }
    }

    public function assertPuedeCrearPromocion(Vendor $vendor): void
    {
        $limite = self::datos($this->planActual($vendor))['max_promociones'] ?? null;
        if ($limite === null) {
            return;
        }

        $activas = Promocion::query()->where('vendor_id', $vendor->id)->where('activa', true)->count();
        if ($activas >= $limite) {
            throw new RuntimeException($limite === 1
                ? 'Tu plan permite 1 promoción activa. Pausa otra o cambia de plan.'
                : "Tu plan permite {$limite} promociones activas. Pausa otra o cambia de plan.");
        }
    }

    public function permiteCargaMasiva(Vendor $vendor): bool
    {
        return (bool) (self::datos($this->planActual($vendor))['carga_masiva'] ?? false);
    }

    /**
     * Cuantos servicios incluidos en el plan (oferta destacada o banner) le quedan este mes.
     */
    public function incluidosDisponibles(Vendor $vendor, string $tipo): int
    {
        $clave = $tipo === 'banner' ? 'banners_incluidos' : 'destacados_incluidos';
        $incluidos = (int) (self::datos($this->planActual($vendor))[$clave] ?? 0);

        if ($incluidos === 0) {
            return 0;
        }

        $usados = ServicioContratado::query()
            ->where('vendor_id', $vendor->id)
            ->where('tipo', $tipo)
            ->where('monto', 0)
            ->whereNotIn('estado', ['rechazado', 'cancelado'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return max(0, $incluidos - $usados);
    }

    public function tienePrioridad(Vendor $vendor): bool
    {
        return (bool) (self::datos($this->planActual($vendor))['prioridad_listado'] ?? false);
    }

    /**
     * Planes que van primero en el listado de tiendas.
     *
     * @return array<int, string>
     */
    public static function planesConPrioridad(): array
    {
        return array_keys(array_filter(self::todos(), fn (array $plan): bool => (bool) ($plan['prioridad_listado'] ?? false)));
    }
}
