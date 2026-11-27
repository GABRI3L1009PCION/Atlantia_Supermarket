<?php

namespace App\Services\Admin;

use App\Models\CourierProfile;
use App\Models\Dte\DteFactura;
use App\Models\LiquidacionVendedor;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Reportes\UtilidadMensualService;
use App\Support\HorarioTienda;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Vista general del dueño: como va el negocio hoy y que hay que atender.
 *
 * Las ventas se cuentan sobre los pedidos "hoja" (el pedido de cada tienda): un pedido de
 * una sola tienda o cada hijo de un pedido multitienda. Asi no se cuenta doble el padre.
 */
class NegocioDashboardService
{
    private const ESTADOS_NO_VENTA = ['cancelado', 'rechazado'];

    private const ESTADOS_EN_CURSO = ['pendiente', 'confirmado', 'en_revision', 'preparando', 'listo_para_entrega', 'en_ruta'];

    public function __construct(private readonly UtilidadMensualService $utilidad) {}

    /**
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        $atlantiaId = (int) Vendor::query()->where('slug', config('atlantia.vendor_propio_slug', 'atlantia-supermarket'))->value('id');

        return [
            'kpis' => $this->kpis(),
            'pendientes' => $this->pendientes($atlantiaId),
            'ventas_dias' => $this->ventasPorDia($atlantiaId, 14),
            'en_curso' => $this->pedidosEnCurso(),
            'top_productos' => $this->topProductos($atlantiaId),
            'operacion' => $this->operacion(),
        ];
    }

    /**
     * @return Builder<Pedido>
     */
    private function ventas(): Builder
    {
        return Pedido::query()
            ->whereDoesntHave('pedidosHijos')
            ->whereNotIn('estado', self::ESTADOS_NO_VENTA);
    }

    /**
     * @return array<string, array{valor: float, anterior: float, cambio: ?float}>
     */
    private function kpis(): array
    {
        $hoy = today();
        $ayer = today()->subDay();
        $mes = now()->startOfMonth();
        $mesAnterior = now()->subMonthNoOverflow()->startOfMonth();

        $ventasHoy = (float) $this->ventas()->whereDate('created_at', $hoy)->sum('total');
        $ventasAyer = (float) $this->ventas()->whereDate('created_at', $ayer)->sum('total');
        $pedidosHoy = (float) $this->pedidosCliente()->whereDate('created_at', $hoy)->count();
        $pedidosAyer = (float) $this->pedidosCliente()->whereDate('created_at', $ayer)->count();
        $utilidadMes = (float) ($this->utilidad->calcular($mes)['utilidad'] ?? 0);
        $utilidadAnterior = (float) ($this->utilidad->calcular($mesAnterior)['utilidad'] ?? 0);

        return [
            'ventas_hoy' => $this->kpi($ventasHoy, $ventasAyer),
            'pedidos_hoy' => $this->kpi($pedidosHoy, $pedidosAyer),
            'utilidad_mes' => $this->kpi($utilidadMes, $utilidadAnterior),
            'ticket_promedio' => $this->kpi($this->ticket($mes, now()), $this->ticket($mesAnterior, $mesAnterior->copy()->endOfMonth())),
        ];
    }

    /**
     * Pedidos tal como los hace el cliente (el padre cuenta como uno).
     *
     * @return Builder<Pedido>
     */
    private function pedidosCliente(): Builder
    {
        return Pedido::query()->padres()->whereNotIn('estado', self::ESTADOS_NO_VENTA);
    }

    private function ticket(Carbon $desde, Carbon $hasta): float
    {
        return round((float) $this->pedidosCliente()->whereBetween('created_at', [$desde, $hasta])->avg('total'), 2);
    }

    /**
     * @return array{valor: float, anterior: float, cambio: ?float}
     */
    private function kpi(float $valor, float $anterior): array
    {
        return [
            'valor' => round($valor, 2),
            'anterior' => round($anterior, 2),
            'cambio' => abs($anterior) < 0.01 ? null : round((($valor - $anterior) / abs($anterior)) * 100, 1),
        ];
    }

    /**
     * Lo que el dueño tiene que resolver, del mas urgente al menos urgente.
     *
     * @return Collection<int, array{nivel: string, titulo: string, detalle: string, url: string}>
     */
    private function pendientes(int $atlantiaId): Collection
    {
        $items = collect();

        $porConfirmar = Pedido::query()->whereDoesntHave('pedidosHijos')->where('vendor_id', $atlantiaId)->where('estado', 'pendiente');
        $cantidad = (clone $porConfirmar)->count();
        if ($cantidad > 0) {
            $minutos = (int) Carbon::parse((clone $porConfirmar)->min('created_at'))->diffInMinutes(now());
            $items->push([
                'nivel' => 'alta',
                'titulo' => $cantidad === 1 ? '1 pedido de Atlantia Store esperando confirmación' : "{$cantidad} pedidos de Atlantia Store esperando confirmación",
                'detalle' => 'El más antiguo lleva '.$this->tiempo($minutos).'.',
                'url' => route('admin.pedidos.index', ['estado' => 'pendiente']),
            ]);
        }

        $atrasados = Pedido::query()->whereDoesntHave('pedidosHijos')->where('vendor_id', '!=', $atlantiaId)
            ->where('estado', 'pendiente')->where('created_at', '<=', now()->subMinutes(15))->count();
        if ($atrasados > 0) {
            $items->push([
                'nivel' => 'alta',
                'titulo' => "{$atrasados} ".($atrasados === 1 ? 'pedido' : 'pedidos').' de tiendas locales sin confirmar',
                'detalle' => 'Llevan más de 15 minutos sin respuesta de la tienda.',
                'url' => route('admin.pedidos.index', ['estado' => 'pendiente']),
            ]);
        }

        $stockBajo = Producto::query()->where('vendor_id', $atlantiaId)->where('is_active', true)
            ->whereHas('inventario', fn ($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo'))->count();
        if ($stockBajo > 0) {
            $items->push([
                'nivel' => 'media',
                'titulo' => "{$stockBajo} ".($stockBajo === 1 ? 'producto' : 'productos').' de Atlantia Store con poco stock',
                'detalle' => 'Están en su mínimo o por debajo. Reabastece para no perder ventas.',
                'url' => route('admin.productos.index'),
            ]);
        }

        $solicitudes = Vendor::query()->pending()->count();
        if ($solicitudes > 0) {
            $items->push([
                'nivel' => 'media',
                'titulo' => "{$solicitudes} ".($solicitudes === 1 ? 'solicitud' : 'solicitudes').' de vendedor por aprobar',
                'detalle' => 'Negocios que quieren vender en Atlantia Store.',
                'url' => route('admin.vendedores.index'),
            ]);
        }

        $liquidaciones = LiquidacionVendedor::query()->where('estado', 'solicitada');
        $cantidadLiq = (clone $liquidaciones)->count();
        if ($cantidadLiq > 0) {
            $items->push([
                'nivel' => 'media',
                'titulo' => "{$cantidadLiq} ".($cantidadLiq === 1 ? 'pago a vendedor solicitado' : 'pagos a vendedores solicitados'),
                'detalle' => 'Total por pagar: Q '.number_format((float) (clone $liquidaciones)->sum('monto_neto'), 2).'.',
                'url' => route('admin.liquidaciones.index'),
            ]);
        }

        $fraude = FraudAlert::query()->where('resuelta', false)->count();
        if ($fraude > 0) {
            $items->push([
                'nivel' => 'media',
                'titulo' => "{$fraude} ".($fraude === 1 ? 'alerta' : 'alertas').' de posible fraude',
                'detalle' => 'Revisa los pedidos marcados antes de despacharlos.',
                'url' => route('admin.antifraude.index'),
            ]);
        }

        $dte = DteFactura::query()->where('estado', 'rechazado')->count();
        if ($dte > 0) {
            $items->push([
                'nivel' => 'media',
                'titulo' => "{$dte} ".($dte === 1 ? 'factura rechazada' : 'facturas rechazadas').' por SAT',
                'detalle' => 'Hay que corregirlas y volver a enviarlas.',
                'url' => route('admin.dte.index'),
            ]);
        }

        $sinCosto = Producto::query()->where('vendor_id', $atlantiaId)->where('is_active', true)->whereNull('costo_compra')->count();
        if ($sinCosto > 0) {
            $items->push([
                'nivel' => 'baja',
                'titulo' => "{$sinCosto} ".($sinCosto === 1 ? 'producto' : 'productos').' sin costo de compra',
                'detalle' => 'Sin el costo, la utilidad del mes no sale exacta.',
                'url' => route('admin.productos.index'),
            ]);
        }

        return $items;
    }

    private function tiempo(int $minutos): string
    {
        if ($minutos < 60) {
            return max(1, $minutos).' min';
        }

        $horas = intdiv($minutos, 60);

        return $horas < 24 ? "{$horas} h ".($minutos % 60).' min' : intdiv($horas, 24).' días';
    }

    /**
     * Ventas por día, separando Atlantia Store y vendedores locales.
     *
     * @return Collection<int, array{fecha: Carbon, atlantia: float, locales: float, total: float}>
     */
    private function ventasPorDia(int $atlantiaId, int $dias): Collection
    {
        $desde = today()->subDays($dias - 1);
        $filas = $this->ventas()
            ->where('created_at', '>=', $desde)
            ->get(['vendor_id', 'total', 'created_at'])
            ->groupBy(fn (Pedido $p): string => $p->created_at->toDateString());

        return collect(range(0, $dias - 1))->map(function (int $i) use ($desde, $filas, $atlantiaId): array {
            $fecha = $desde->copy()->addDays($i);
            $delDia = $filas->get($fecha->toDateString(), collect());
            $atlantia = (float) $delDia->where('vendor_id', $atlantiaId)->sum('total');
            $total = (float) $delDia->sum('total');

            return ['fecha' => $fecha, 'atlantia' => round($atlantia, 2), 'locales' => round($total - $atlantia, 2), 'total' => round($total, 2)];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pedidosEnCurso(): Collection
    {
        return Pedido::query()
            ->whereDoesntHave('pedidosHijos')
            ->whereIn('estado', self::ESTADOS_EN_CURSO)
            ->with(['vendor:id,business_name', 'cliente:id,name', 'deliveryRoute.repartidor:id,name', 'pedidoPadre:id,uuid,numero_pedido'])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Pedido $pedido): array => [
                'uuid' => $pedido->uuid,
                'numero' => $pedido->numero_pedido,
                'tienda' => $pedido->vendor?->business_name ?? 'Tienda',
                'cliente' => $pedido->cliente?->name ?? 'Cliente',
                'total' => (float) $pedido->total,
                'estado' => $pedido->estado instanceof BackedEnum ? $pedido->estado->value : (string) $pedido->estado,
                'repartidor' => $pedido->deliveryRoute?->repartidor?->name,
                'hace' => $pedido->created_at?->diffForHumans(short: true),
            ]);
    }

    /**
     * Lo mas vendido de Atlantia Store en los ultimos 7 dias.
     *
     * @return Collection<int, array{nombre: string, cantidad: int, venta: float}>
     */
    private function topProductos(int $atlantiaId): Collection
    {
        return PedidoItem::query()
            ->whereHas('pedido', fn ($q) => $q->where('vendor_id', $atlantiaId)
                ->whereNotIn('estado', self::ESTADOS_NO_VENTA)
                ->where('created_at', '>=', today()->subDays(6)))
            ->selectRaw('producto_nombre_snapshot as nombre, SUM(cantidad) as cantidad, SUM(subtotal) as venta')
            ->groupBy('producto_nombre_snapshot')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get()
            ->map(fn ($fila): array => ['nombre' => (string) $fila->nombre, 'cantidad' => (int) $fila->cantidad, 'venta' => round((float) $fila->venta, 2)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function operacion(): array
    {
        $tiendas = Vendor::query()->approved()->get(['id', 'business_name', 'hora_apertura', 'hora_cierre', 'pausado_hasta']);
        $abiertas = $tiendas->filter(fn (Vendor $v): bool => HorarioTienda::estado($v)['abierto'])->count();

        $mes = now()->startOfMonth();
        $zona = fn (string $patron): int => $this->pedidosCliente()->where('created_at', '>=', $mes)
            ->whereHas('direccion', fn ($q) => $q->where('municipio', 'like', $patron))->count();

        return [
            'repartidores_disponibles' => CourierProfile::query()->where('availability_status', 'available')->count(),
            'repartidores_ocupados' => CourierProfile::query()->where('availability_status', 'busy')->count(),
            'pedidos_en_ruta' => Pedido::query()->whereDoesntHave('pedidosHijos')->where('estado', 'en_ruta')->count(),
            'tiendas_abiertas' => $abiertas,
            'tiendas_total' => $tiendas->count(),
            'zonas' => [
                'Puerto Barrios' => $zona('Puerto Barrios%'),
                'Santo Tomás de Castilla' => $zona('Santo Tom%'),
            ],
        ];
    }
}
