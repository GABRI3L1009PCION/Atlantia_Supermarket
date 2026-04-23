<?php

namespace App\Services\Comisiones;

use App\Exceptions\TransaccionFallidaException;
use App\Models\PaymentSplit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de calculo y conciliacion de comisiones mensuales.
 */
class CalculadoraComisionService
{
    /**
     * Pagina comisiones para administracion.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return VendorCommission::query()
            ->with(['vendor', 'dteComision'])
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->when($filters['anio'] ?? null, fn ($query, $anio) => $query->where('anio', $anio))
            ->when($filters['mes'] ?? null, fn ($query, $mes) => $query->where('mes', $mes))
            ->latest()
            ->paginate(50);
    }

    /**
     * Resume indicadores del panel de comisiones.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $query = VendorCommission::query()
            ->when($filters['anio'] ?? null, fn ($builder, $anio) => $builder->where('anio', $anio))
            ->when($filters['mes'] ?? null, fn ($builder, $mes) => $builder->where('mes', $mes));

        $comisiones = (clone $query)->get();

        return [
            'total_periodo' => round((float) $comisiones->sum('monto_total'), 2),
            'pendientes' => $comisiones->where('estado', 'pendiente')->count(),
            'facturadas' => $comisiones->where('estado', 'facturada')->count(),
            'pagadas' => $comisiones->where('estado', 'pagada')->count(),
            'vencidas' => $comisiones->where('estado', 'vencida')->count(),
            'top_vendedores' => $this->topVendedores($comisiones),
        ];
    }

    /**
     * Pagina comisiones del vendedor autenticado.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function paginateForVendor(User $user): LengthAwarePaginator
    {
        return VendorCommission::query()
            ->with(['dteComision'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->paginate(25);
    }

    /**
     * Devuelve la comision activa reciente del vendedor con cache corto.
     *
     * @param Vendor $vendor
     * @return VendorCommission|null
     */
    public function activeForVendorCached(Vendor $vendor): ?VendorCommission
    {
        return Cache::remember(
            "vendor_commissions:active:{$vendor->id}",
            now()->addMinutes(15),
            fn (): ?VendorCommission => VendorCommission::query()
                ->where('vendor_id', $vendor->id)
                ->whereIn('estado', ['pendiente', 'facturada', 'vencida'])
                ->latest('fecha_emision')
                ->first()
        );
    }

    /**
     * Carga detalle de una comision.
     *
     * @param VendorCommission $commission
     * @return VendorCommission
     */
    public function detail(VendorCommission $commission): VendorCommission
    {
        return $commission->load(['vendor.user', 'dteComision']);
    }

    /**
     * Calcula o actualiza comision mensual de un vendedor.
     *
     * @param Vendor $vendor
     * @param int $anio
     * @param int $mes
     * @return VendorCommission
     *
     * @throws TransaccionFallidaException
     */
    public function calcularMensual(Vendor $vendor, int $anio, int $mes): VendorCommission
    {
        try {
            return DB::transaction(function () use ($vendor, $anio, $mes): VendorCommission {
                $periodo = $this->periodo($anio, $mes);
                $totales = $this->totalesPeriodo($vendor, $periodo['inicio'], $periodo['fin']);
                $porcentaje = (float) $vendor->commission_percentage;
                $rentaFija = (float) $vendor->monthly_rent;
                $montoComision = round($totales['total_ventas'] * ($porcentaje / 100), 2);
                $montoTotal = round($montoComision + $rentaFija, 2);

                return VendorCommission::query()->updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'anio' => $anio,
                        'mes' => $mes,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'total_ventas' => $totales['total_ventas'],
                        'commission_percentage' => $porcentaje,
                        'monto_comision' => $montoComision,
                        'renta_fija' => $rentaFija,
                        'monto_total' => $montoTotal,
                        'estado' => 'pendiente',
                        'fecha_emision' => now()->toDateString(),
                        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
                    ]
                );
            });
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible calcular la comision mensual.', previous: $exception);
        }
    }

    /**
     * Calcula comisiones para todos los vendedores aprobados.
     *
     * @param int $anio
     * @param int $mes
     * @return int
     */
    public function calcularPeriodoGlobal(int $anio, int $mes): int
    {
        $procesadas = 0;

        Vendor::query()
            ->approved()
            ->chunkById(100, function ($vendors) use ($anio, $mes, &$procesadas): void {
                foreach ($vendors as $vendor) {
                    $this->calcularMensual($vendor, $anio, $mes);
                    $procesadas++;
                }
            });

        return $procesadas;
    }

    /**
     * Actualiza datos administrativos de una comision.
     *
     * @param VendorCommission $commission
     * @param array<string, mixed> $data
     * @param User $user
     * @return VendorCommission
     */
    public function update(VendorCommission $commission, array $data, User $user): VendorCommission
    {
        $payload = [
            'estado' => $data['estado'] ?? $commission->estado,
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? $commission->fecha_vencimiento,
        ];

        if (($data['estado'] ?? null) === 'pagada') {
            $payload['pagada_at'] = now();
        }

        $commission->update($payload);

        return $commission->refresh();
    }

    /**
     * Devuelve coleccion resumida de vendedores con mayor comision.
     *
     * @param Collection<int, VendorCommission> $comisiones
     * @return Collection<int, array<string, mixed>>
     */
    private function topVendedores(Collection $comisiones): Collection
    {
        return $comisiones
            ->loadMissing('vendor')
            ->sortByDesc('monto_total')
            ->take(5)
            ->map(fn (VendorCommission $comision): array => [
                'vendor' => $comision->vendor?->business_name ?? 'Sin vendedor',
                'monto_total' => (float) $comision->monto_total,
                'estado' => $comision->estado,
                'periodo' => sprintf('%02d/%d', $comision->mes, $comision->anio),
            ])
            ->values();
    }

    /**
     * Marca la comision como facturada al asociar su DTE.
     *
     * @param VendorCommission $commission
     * @param int $dteId
     * @return VendorCommission
     */
    public function marcarFacturada(VendorCommission $commission, int $dteId): VendorCommission
    {
        $commission->update([
            'estado' => 'facturada',
            'dte_comision_id' => $dteId,
            'fecha_emision' => $commission->fecha_emision ?? now()->toDateString(),
        ]);

        return $commission->refresh();
    }

    /**
     * Calcula totales cobrables del periodo usando splits aprobados/liquidados.
     *
     * @param Vendor $vendor
     * @param Carbon $inicio
     * @param Carbon $fin
     * @return array<string, float>
     */
    private function totalesPeriodo(Vendor $vendor, Carbon $inicio, Carbon $fin): array
    {
        $totalVentas = PaymentSplit::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('estado', ['pendiente', 'liquidado'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->sum('monto_bruto');

        return ['total_ventas' => round((float) $totalVentas, 2)];
    }

    /**
     * Devuelve rango de fechas mensual.
     *
     * @param int $anio
     * @param int $mes
     * @return array<string, Carbon>
     */
    private function periodo(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();

        return [
            'inicio' => $inicio,
            'fin' => $inicio->copy()->endOfMonth()->endOfDay(),
        ];
    }
}
