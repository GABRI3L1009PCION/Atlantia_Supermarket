<?php

namespace App\Services\Vendedores;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\LiquidacionVendedor;
use App\Models\PaymentSplit;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PagoTiendaPendienteNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Pagos a vendedores. Todo el dinero de las ventas entra a la cuenta de Atlantia; el vendedor
 * solicita su pago y recibe sus ventas ENTREGADAS menos la comision de su plan y la tarifa de
 * la pasarela, que se calcula venta por venta (por transaccion), nunca sobre el total.
 */
class LiquidacionesVendedorService
{
    private const PAGOS_VALIDOS = [
        EstadoPago::Pendiente->value,
        EstadoPago::Validando->value,
        EstadoPago::Aprobado->value,
        EstadoPago::Pagado->value,
    ];

    /**
     * Ventas entregadas que todavia no se le han pagado ni estan en una solicitud.
     *
     * @return Builder<PaymentSplit>
     */
    public function disponiblesQuery(Vendor $vendor): Builder
    {
        $query = $this->ventasQuery($vendor)
            ->whereNull('liquidacion_id')
            ->where('estado', 'pendiente')
            ->whereHas('pedido', fn (Builder $q) => $q->where('estado', EstadoPedido::Entregado->value));

        /* Tienda nueva: el dinero de cada venta entregada se libera unos días después (por si hay reclamos). */
        if (self::esTiendaNueva($vendor)) {
            $limite = now()->subDays((int) config('atlantia.vendedores.dias_retencion_nueva', 7));
            $query->whereHas('pedido.estados', fn (Builder $q) => $q->where('estado', EstadoPedido::Entregado->value)->where('created_at', '<=', $limite));
        }

        return $query;
    }

    /**
     * Ventas entregadas que todavía están en el periodo de retención de tienda nueva.
     *
     * @return Builder<PaymentSplit>
     */
    public function retenidasQuery(Vendor $vendor): Builder
    {
        if (! self::esTiendaNueva($vendor)) {
            return $this->ventasQuery($vendor)->whereRaw('1 = 0');
        }

        $limite = now()->subDays((int) config('atlantia.vendedores.dias_retencion_nueva', 7));

        return $this->ventasQuery($vendor)
            ->whereNull('liquidacion_id')
            ->where('estado', 'pendiente')
            ->whereHas('pedido', fn (Builder $q) => $q->where('estado', EstadoPedido::Entregado->value))
            ->whereDoesntHave('pedido.estados', fn (Builder $q) => $q->where('estado', EstadoPedido::Entregado->value)->where('created_at', '<=', $limite));
    }

    public static function esTiendaNueva(Vendor $vendor): bool
    {
        return $vendor->approved_at !== null
            && $vendor->approved_at->gt(now()->subDays((int) config('atlantia.vendedores.dias_tienda_nueva', 14)));
    }

    /** Frecuencias que la tienda puede elegir para recibir su dinero (todas sin costo). */
    public const FRECUENCIAS = [
        'instantanea' => 'Al instante (de 1 a 2 horas)',
        'diaria' => 'Cada día',
        'semanal' => 'Cada semana (lunes)',
        'mensual' => 'Cada mes (día 1)',
    ];

    /**
     * Crea los pagos que tocan según la frecuencia de cada tienda y avisa a quien transfiere.
     * Se corre cada pocos minutos. Devuelve cuántos pagos creó.
     */
    public function programarPagosAutomaticos(): int
    {
        $creados = 0;

        Vendor::query()->where('status', 'approved')->whereNull('pagos_retenidos_at')
            ->whereHas('fiscalProfile', fn (Builder $q) => $q->whereNotNull('cuenta_bancaria')->where('cuenta_bancaria', '!=', ''))
            ->with('fiscalProfile')
            ->each(function (Vendor $vendor) use (&$creados): void {
                $frecuencia = (string) ($vendor->payment_frequency ?: 'semanal');
                $inicio = self::inicioPeriodo($frecuencia);
                if ($inicio === null || now()->lt($inicio)) {
                    return;
                }

                $yaTiene = LiquidacionVendedor::query()->where('vendor_id', $vendor->id)->where('automatica', true)
                    ->where('solicitada_at', '>=', $inicio)->exists();
                if ($frecuencia !== 'instantanea' && $yaTiene) {
                    return;
                }

                try {
                    $liquidacion = $this->solicitar($vendor, automatica: true);
                    $creados++;
                    $this->avisarPago($liquidacion);
                } catch (ValidationException) {
                    /* Sin ventas por cobrar, o debajo del mínimo: no hay nada que pagar todavía. */
                }
            });

        return $creados;
    }

    /**
     * Desde cuándo corre el periodo de pago actual (null = no se programa).
     */
    private static function inicioPeriodo(string $frecuencia): ?Carbon
    {
        $hoy8 = now()->setTime(8, 0);

        return match ($frecuencia) {
            'instantanea' => now()->startOfDay(),
            'diaria' => $hoy8,
            'semanal' => now()->startOfWeek(Carbon::MONDAY)->setTime(8, 0),
            'quincenal' => now()->day >= 16 ? now()->setDay(16)->setTime(8, 0) : now()->setDay(1)->setTime(8, 0),
            'mensual' => now()->startOfMonth()->setTime(8, 0),
            default => null,
        };
    }

    /**
     * Plazo para transferir: 1-2 horas si es al instante; si no, el mismo día hábil.
     */
    public static function plazoPara(string $frecuencia): Carbon
    {
        if ($frecuencia === 'instantanea') {
            return now()->addHours((int) config('atlantia.vendedores.horas_pago_instantaneo', 2));
        }

        return now()->hour < 16 ? now()->setTime(18, 0) : now()->addDay()->setTime(12, 0);
    }

    private function avisarPago(LiquidacionVendedor $liquidacion): void
    {
        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
            if ($roles !== []) {
                Notification::send(User::query()->role($roles)->get(), new PagoTiendaPendienteNotification($liquidacion));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Ventas cobradas cuyo pedido aun no se entrega: se podran cobrar al entregarse.
     *
     * @return Builder<PaymentSplit>
     */
    public function enCaminoQuery(Vendor $vendor): Builder
    {
        return $this->ventasQuery($vendor)
            ->whereNull('liquidacion_id')
            ->where('estado', 'pendiente')
            ->whereHas('pedido', fn (Builder $q) => $q->whereNotIn('estado', [
                EstadoPedido::Entregado->value,
                EstadoPedido::Cancelado->value,
                EstadoPedido::Rechazado->value,
            ]));
    }

    /**
     * @return array{disponible: array<string, float|int>, en_camino: array<string, float|int>, pagado: float, en_proceso: float}
     */
    public function resumen(Vendor $vendor): array
    {
        return [
            'disponible' => $this->totales($this->disponiblesQuery($vendor)->get()),
            'en_camino' => $this->totales($this->enCaminoQuery($vendor)->get()),
            'pagado' => (float) LiquidacionVendedor::query()->where('vendor_id', $vendor->id)->where('estado', 'pagada')->sum('monto_neto'),
            'en_proceso' => (float) LiquidacionVendedor::query()->where('vendor_id', $vendor->id)->where('estado', 'solicitada')->sum('monto_neto'),
        ];
    }

    /**
     * @param  Collection<int, PaymentSplit>  $ventas
     * @return array{ventas: int, bruto: float, plataforma: float, pasarela: float, neto: float}
     */
    public function totales(Collection $ventas): array
    {
        return [
            'ventas' => $ventas->count(),
            'bruto' => round((float) $ventas->sum('monto_bruto'), 2),
            'plataforma' => round((float) $ventas->sum('comision_atlantia'), 2),
            'pasarela' => round((float) $ventas->sum('comision_pasarela'), 2),
            'neto' => round((float) $ventas->sum('monto_neto_vendedor'), 2),
        ];
    }

    /**
     * El vendedor pide su pago: todas sus ventas disponibles pasan a una solicitud.
     */
    public function solicitar(Vendor $vendor, bool $automatica = false): LiquidacionVendedor
    {
        if ($vendor->pagos_retenidos_at !== null) {
            throw ValidationException::withMessages(['pago' => 'Tus pagos están en revisión por Atlantia. Escríbenos a soporte si tienes dudas.']);
        }

        return DB::transaction(function () use ($vendor, $automatica): LiquidacionVendedor {
            $ventas = $this->disponiblesQuery($vendor)->lockForUpdate()->get();
            $totales = $this->totales($ventas);
            $minimo = (float) config('monetizacion.pago_minimo_vendedor', 0);

            if ($ventas->isEmpty() || $totales['neto'] <= 0) {
                throw ValidationException::withMessages(['pago' => 'Aún no tienes ventas entregadas pendientes de pago.']);
            }

            if ($totales['neto'] < $minimo) {
                throw ValidationException::withMessages(['pago' => 'El monto mínimo para solicitar tu pago es Q '.number_format($minimo, 2).'.']);
            }

            $perfil = $vendor->fiscalProfile;

            $liquidacion = LiquidacionVendedor::query()->create([
                'uuid' => (string) Str::uuid(),
                'numero' => 'LIQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'vendor_id' => $vendor->id,
                'ventas' => $totales['ventas'],
                'monto_bruto' => $totales['bruto'],
                'comision_plataforma' => $totales['plataforma'],
                'comision_pasarela' => $totales['pasarela'],
                'monto_neto' => $totales['neto'],
                'estado' => 'solicitada',
                'automatica' => $automatica,
                'pagar_antes_de' => self::plazoPara((string) ($vendor->payment_frequency ?: 'semanal')),
                'banco_nombre' => $perfil?->banco_nombre,
                'cuenta_titular' => $perfil?->cuenta_bancaria_titular,
                'solicitada_at' => now(),
            ]);

            PaymentSplit::query()->whereKey($ventas->modelKeys())->update(['liquidacion_id' => $liquidacion->id]);

            return $liquidacion;
        });
    }

    /**
     * Atlantia ya transfirio: las ventas quedan pagadas y salen del saldo del vendedor.
     */
    public function marcarPagada(LiquidacionVendedor $liquidacion, string $referencia, User $admin): LiquidacionVendedor
    {
        if ($liquidacion->estado !== 'solicitada') {
            throw ValidationException::withMessages(['referencia_pago' => 'Esta solicitud ya fue procesada.']);
        }

        DB::transaction(function () use ($liquidacion, $referencia, $admin): void {
            $liquidacion->update([
                'estado' => 'pagada',
                'referencia_pago' => $referencia,
                'pagada_at' => now(),
                'pagada_por' => $admin->id,
            ]);
            $liquidacion->splits()->update(['estado' => 'liquidado', 'liquidado_at' => now()]);
        });

        return $liquidacion->refresh();
    }

    /**
     * Se rechaza la solicitud: las ventas vuelven al saldo disponible del vendedor.
     */
    public function rechazar(LiquidacionVendedor $liquidacion, ?string $motivo): LiquidacionVendedor
    {
        if ($liquidacion->estado !== 'solicitada') {
            throw ValidationException::withMessages(['notas' => 'Esta solicitud ya fue procesada.']);
        }

        DB::transaction(function () use ($liquidacion, $motivo): void {
            $liquidacion->splits()->update(['liquidacion_id' => null]);
            $liquidacion->update(['estado' => 'rechazada', 'notas' => $motivo]);
        });

        return $liquidacion->refresh();
    }

    /**
     * Detalle legible de la tarifa de pasarela de una venta (por transaccion).
     */
    public function detalleTarifa(PaymentSplit $venta): string
    {
        if ((float) $venta->comision_pasarela <= 0) {
            return 'Sin tarifa (no fue con tarjeta)';
        }

        $config = (array) config('monetizacion.pasarela', []);
        $tiendas = max(1, (int) PaymentSplit::query()->where('payment_id', $venta->payment_id)->count());
        $fijo = round((float) ($config['fijo'] ?? 0) / $tiendas, 2);
        $texto = rtrim(rtrim(number_format((float) ($config['porcentaje'] ?? 0), 2), '0'), '.').' % de esta venta + Q '.number_format($fijo, 2);

        return $tiendas > 1 ? $texto.' (Q '.number_format((float) ($config['fijo'] ?? 0), 2).' del pago dividido entre '.$tiendas.' tiendas)' : $texto;
    }

    /**
     * @return Builder<PaymentSplit>
     */
    private function ventasQuery(Vendor $vendor): Builder
    {
        return PaymentSplit::query()
            ->with(['pedido', 'payment'])
            ->where('vendor_id', $vendor->id)
            ->whereHas('payment', fn (Builder $q) => $q->whereIn('estado', self::PAGOS_VALIDOS))
            ->latest('id');
    }
}
