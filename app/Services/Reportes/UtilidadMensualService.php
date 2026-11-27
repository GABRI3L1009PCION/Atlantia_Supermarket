<?php

namespace App\Services\Reportes;

use App\Enums\EstadoPedido;
use App\Models\CourierWalletMovement;
use App\Models\GastoFijo;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\ServicioContratado;
use App\Models\Vendor;
use Illuminate\Support\Carbon;

/**
 * Utilidad del mes de Atlantia: lo que entra por cada fuente menos tarifas de tarjeta,
 * pagos a repartidores y gastos fijos. Se toman los pedidos entregados en el mes.
 */
class UtilidadMensualService
{
    /**
     * @return array<string, mixed>
     */
    public function calcular(Carbon $mes): array
    {
        $inicio = $mes->copy()->startOfMonth();
        $fin = $mes->copy()->endOfMonth();
        $atlantiaId = (int) Vendor::query()->where('slug', 'atlantia-supermarket')->value('id');

        $entregados = Pedido::query()
            ->whereNotNull('vendor_id')
            ->where('estado', EstadoPedido::Entregado->value)
            ->whereBetween('updated_at', [$inicio, $fin]);
        $idsEntregados = (clone $entregados)->pluck('id');
        $idsAtlantia = (clone $entregados)->where('vendor_id', $atlantiaId)->pluck('id');

        /* Margen de Atlantia Store: venta menos costo de compra (solo productos con costo registrado). */
        $itemsAtlantia = PedidoItem::query()->with('producto:id,costo_compra')->whereIn('pedido_id', $idsAtlantia)->get();
        $ventaAtlantia = round((float) $itemsAtlantia->sum('subtotal'), 2);
        $conCosto = $itemsAtlantia->filter(fn (PedidoItem $item) => $item->producto?->costo_compra !== null);
        $margenAtlantia = round((float) $conCosto->sum(fn (PedidoItem $item) => (float) $item->subtotal - (float) $item->producto->costo_compra * (int) $item->cantidad), 2);

        /* Ventas de vendedores locales: comision del plan y tarifas de tarjeta que ellos absorben. */
        $splitsVendedores = PaymentSplit::query()->whereIn('pedido_id', $idsEntregados)->where('vendor_id', '!=', $atlantiaId)->get();
        $comisiones = round((float) $splitsVendedores->sum('comision_atlantia'), 2);
        $tarifasCobradasVendedores = round((float) $splitsVendedores->sum('comision_pasarela'), 2);

        /* Envios: lo cobrado al cliente menos lo pagado a los repartidores. */
        $envios = round((float) (clone $entregados)->sum('envio'), 2);
        $pagoRepartidores = round((float) CourierWalletMovement::query()->where('type', 'earning')->whereIn('pedido_id', $idsEntregados)->sum('amount'), 2);

        /* Tarifa real de Recurrente: por cada pago con tarjeta de esos pedidos. */
        $config = (array) config('monetizacion.pasarela', []);
        $idsPadres = Pedido::query()->whereIn('id', $idsEntregados)->pluck('pedido_padre_id')->filter()->merge($idsEntregados)->unique();
        $pagosTarjeta = Payment::query()->whereIn('pedido_id', $idsPadres)->where('metodo', 'tarjeta')->whereIn('estado', ['aprobado', 'pagado'])->get(['id', 'monto']);
        $tarifasTarjeta = round((float) $pagosTarjeta->sum(fn (Payment $p) => (float) $p->monto * ((float) ($config['porcentaje'] ?? 0) / 100) + (float) ($config['fijo'] ?? 0)), 2);

        $planesYPublicidad = round((float) ServicioContratado::query()->whereNotNull('pagado_at')->whereBetween('pagado_at', [$inicio, $fin])->sum('monto'), 2);
        $gastos = GastoFijo::query()->whereDate('mes', $inicio->toDateString())->orderBy('categoria')->get();
        $gastosFijos = round((float) $gastos->sum('monto'), 2);

        $ingresos = [
            ['concepto' => 'Margen de productos de Atlantia Store', 'monto' => $margenAtlantia, 'nota' => $conCosto->count() < $itemsAtlantia->count() ? ($itemsAtlantia->count() - $conCosto->count()).' productos vendidos sin costo de compra registrado' : null],
            ['concepto' => 'Comisiones de vendedores (plan Inicial)', 'monto' => $comisiones, 'nota' => null],
            ['concepto' => 'Planes y publicidad cobrados', 'monto' => $planesYPublicidad, 'nota' => null],
            ['concepto' => 'Envíos cobrados a clientes', 'monto' => $envios, 'nota' => null],
            ['concepto' => 'Tarifas de tarjeta descontadas a vendedores', 'monto' => $tarifasCobradasVendedores, 'nota' => null],
        ];
        $egresos = [
            ['concepto' => 'Pago a repartidores', 'monto' => $pagoRepartidores],
            ['concepto' => 'Tarifas de Recurrente ('.count($pagosTarjeta).' pagos con tarjeta)', 'monto' => $tarifasTarjeta],
            ['concepto' => 'Gastos fijos del mes', 'monto' => $gastosFijos],
        ];

        $totalIngresos = round(array_sum(array_column($ingresos, 'monto')), 2);
        $totalEgresos = round(array_sum(array_column($egresos, 'monto')), 2);
        $pedidos = (clone $entregados)->distinct()->count('pedido_padre_id');
        $aportePorPedido = $pedidos > 0 ? round(($totalIngresos - $totalEgresos + $gastosFijos) / $pedidos, 2) : 0.0;

        return [
            'mes' => $inicio,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'utilidad' => round($totalIngresos - $totalEgresos, 2),
            'gastos' => $gastos,
            'pedidos' => $pedidos,
            'venta_atlantia' => $ventaAtlantia,
            'aporte_por_pedido' => $aportePorPedido,
            /* Pedidos al mes para cubrir los gastos fijos con el aporte promedio actual. */
            'pedidos_equilibrio' => $aportePorPedido > 0 ? (int) ceil($gastosFijos / $aportePorPedido) : null,
        ];
    }
}
