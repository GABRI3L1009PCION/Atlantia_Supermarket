<?php

namespace App\Services\Pedidos;

use App\Models\Cliente\Direccion;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Servicio para separar pedidos y pagos por vendedor.
 */
class SplitMultivendedorService
{
    /**
     * Crea pedido padre y pedidos hijos por vendedor desde el carrito.
     *
     * @param User $cliente
     * @param Direccion $direccion
     * @param Collection<int, mixed> $items
     * @param array<string, float> $totals
     * @param array<string, mixed> $data
     * @return Pedido
     */
    public function crearPedidoDesdeCarrito(
        User $cliente,
        Direccion $direccion,
        Collection $items,
        array $totals,
        array $data
    ): Pedido {
        $vendorIds = $items->pluck('producto.vendor_id')->unique()->values();
        $pedidoPadre = $this->crearPedidoBase($cliente, $direccion, null, null, $totals, $data);

        foreach ($items->groupBy('producto.vendor_id') as $vendorId => $vendorItems) {
            $vendorTotals = $this->totalsForItems(
                $vendorItems,
                (float) $totals['envio'] / max(1, $vendorIds->count()),
                (float) ($totals['descuento'] ?? 0),
                (float) ($totals['subtotal'] ?? 0)
            );
            $pedidoHijo = $this->crearPedidoBase($cliente, $direccion, (int) $vendorId, $pedidoPadre, $vendorTotals, $data);

            $this->crearItems($pedidoHijo, $vendorItems);
        }

        return $pedidoPadre;
    }

    /**
     * Crea splits de pago por vendedor.
     *
     * @param Payment $payment
     * @param Pedido $pedidoPadre
     */
    public function crearSplitsDePago(Payment $payment, Pedido $pedidoPadre): void
    {
        $pedidoPadre->pedidosHijos()->with('vendor')->get()->each(function (Pedido $pedidoHijo) use ($payment): void {
            $commissionRate = (float) ($pedidoHijo->vendor?->commission_percentage ?? 0);
            $gross = (float) $pedidoHijo->total;
            $commission = round($gross * ($commissionRate / 100), 2);

            PaymentSplit::query()->create([
                'payment_id' => $payment->id,
                'vendor_id' => $pedidoHijo->vendor_id,
                'monto_bruto' => $gross,
                'comision_atlantia' => $commission,
                'monto_neto_vendedor' => round($gross - $commission, 2),
                'estado' => 'pendiente',
                'liquidado_at' => null,
            ]);
        });
    }

    /**
     * Crea un pedido base.
     *
     * @param User $cliente
     * @param Direccion $direccion
     * @param int|null $vendorId
     * @param Pedido|null $pedidoPadre
     * @param array<string, float> $totals
     * @param array<string, mixed> $data
     * @return Pedido
     */
    private function crearPedidoBase(
        User $cliente,
        Direccion $direccion,
        ?int $vendorId,
        ?Pedido $pedidoPadre,
        array $totals,
        array $data
    ): Pedido {
        return Pedido::query()->create([
            'uuid' => (string) Str::uuid(),
            'numero_pedido' => $this->numeroPedido(),
            'pedido_padre_id' => $pedidoPadre?->id,
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendorId,
            'direccion_id' => $direccion->id,
            'subtotal' => $totals['subtotal'],
            'envio' => $totals['envio'],
            'impuestos' => $totals['impuestos'],
            'descuento' => $totals['descuento'],
            'total' => $totals['total'],
            'estado' => 'pendiente',
            'metodo_pago' => $data['metodo_pago'],
            'estado_pago' => 'pendiente',
            'notas' => $data['notas'] ?? null,
        ]);
    }

    /**
     * Crea items del pedido con snapshot de precio.
     *
     * @param Pedido $pedido
     * @param Collection<int, mixed> $items
     */
    private function crearItems(Pedido $pedido, Collection $items): void
    {
        foreach ($items as $item) {
            $precio = (float) ($item->producto->precio_oferta ?? $item->producto->precio_base);
            $subtotal = round($precio * (int) $item->cantidad, 2);

            PedidoItem::query()->create([
                'pedido_id' => $pedido->id,
                'producto_id' => $item->producto_id,
                'producto_nombre_snapshot' => $item->producto->nombre,
                'producto_sku_snapshot' => $item->producto->sku,
                'cantidad' => $item->cantidad,
                'precio_unitario_snapshot' => $precio,
                'subtotal' => $subtotal,
                'descuento' => 0,
                'impuestos' => round($subtotal * 0.12, 2),
            ]);
        }
    }

    /**
     * Calcula totales para un grupo de items.
     *
     * @param Collection<int, mixed> $items
     * @param float $envio
     * @param float $descuentoGlobal
     * @param float $subtotalGlobal
     * @return array<string, float>
     */
    private function totalsForItems(
        Collection $items,
        float $envio,
        float $descuentoGlobal = 0,
        float $subtotalGlobal = 0
    ): array
    {
        $subtotal = $items->sum(function ($item): float {
            return (float) ($item->producto->precio_oferta ?? $item->producto->precio_base) * (int) $item->cantidad;
        });
        $descuento = $subtotalGlobal > 0
            ? round(($subtotal / $subtotalGlobal) * $descuentoGlobal, 2)
            : 0.0;
        $baseImponible = max(0, $subtotal - $descuento);
        $impuestos = round($baseImponible * 0.12, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'envio' => round($envio, 2),
            'impuestos' => $impuestos,
            'descuento' => $descuento,
            'total' => round($baseImponible + $envio + $impuestos, 2),
        ];
    }

    /**
     * Genera numero humano de pedido.
     *
     * @return string
     */
    private function numeroPedido(): string
    {
        return 'ATL-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }
}
