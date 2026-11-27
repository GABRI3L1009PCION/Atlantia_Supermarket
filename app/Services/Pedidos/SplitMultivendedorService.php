<?php

namespace App\Services\Pedidos;

use App\Models\Cliente\Direccion;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Support\Iva;
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
     * @param  Collection<int, mixed>  $items
     * @param  array<string, float>  $totals
     * @param  array<string, mixed>  $data
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

        $recogerEnTienda = ($data['tipo_entrega'] ?? 'domicilio') === 'recoger';
        /* La propina se reparte entre las entregas; no forma parte de la venta del comercio. */
        $propina = $recogerEnTienda ? 0.0 : round((float) ($totals['propina'] ?? 0), 2);
        $grupos = $items->groupBy('producto.vendor_id');
        $propinaPorEntrega = $grupos->count() > 0 ? floor(($propina / $grupos->count()) * 100) / 100 : 0.0;
        $propinaRestante = $propina;
        $indice = 0;

        foreach ($grupos as $vendorId => $vendorItems) {
            $envioVendedor = $recogerEnTienda || ($totals['envio_gratis'] ?? false) ? 0.0 : (app(DeliveryCoverageService::class)->deliveryCostForVendor(
                $direccion,
                $vendorId === '' ? null : (int) $vendorId
            ) ?? ((float) $totals['envio'] / max(1, $vendorIds->count())));
            $promoVendedor = (float) (($totals['promociones_por_vendor'] ?? [])[$vendorId === '' ? 0 : (int) $vendorId] ?? 0);
            $vendorTotals = $this->totalsForItems(
                $vendorItems,
                $envioVendedor,
                $this->descuentoDeTienda($totals, (int) $vendorId, $vendorItems, $promoVendedor),
                $this->subtotalConPromo($vendorItems, $promoVendedor),
                $promoVendedor
            );
            $indice++;
            $vendorTotals['propina'] = $indice === $grupos->count() ? round($propinaRestante, 2) : $propinaPorEntrega;
            $propinaRestante -= $vendorTotals['propina'];
            $pedidoHijo = $this->crearPedidoBase($cliente, $direccion, (int) $vendorId, $pedidoPadre, $vendorTotals, $data);

            $this->crearItems($pedidoHijo, $vendorItems);
        }

        return $pedidoPadre;
    }

    /**
     * Crea splits de pago por vendedor.
     */
    public function crearSplitsDePago(Payment $payment, Pedido $pedidoPadre): void
    {
        $hijos = $pedidoPadre->pedidosHijos()->with('vendor')->get();
        $tiendas = max(1, $hijos->count());

        $hijos->each(function (Pedido $pedidoHijo) use ($payment, $tiendas): void {
            /* Durante la prueba gratis de la promoción de apertura no se cobra comisión. */
            $commissionRate = (float) ($pedidoHijo->vendor?->comisionVigente() ?? 0);
            /* La venta del comercio es solo su mercaderia: el envio es del repartidor y Atlantia, no del vendedor. */
            $gross = round(max(0, (float) $pedidoHijo->total - (float) $pedidoHijo->envio), 2);
            $commission = round($gross * ($commissionRate / 100), 2);
            $tarifaPasarela = self::tarifaPasarela((string) ($payment->metodo?->value ?? $payment->metodo), $gross, $tiendas);

            PaymentSplit::query()->create([
                'payment_id' => $payment->id,
                'vendor_id' => $pedidoHijo->vendor_id,
                'pedido_id' => $pedidoHijo->id,
                'monto_bruto' => $gross,
                'comision_atlantia' => $commission,
                'comision_pasarela' => $tarifaPasarela,
                'monto_neto_vendedor' => round(max(0, $gross - $commission - $tarifaPasarela), 2),
                'estado' => 'pendiente',
                'liquidado_at' => null,
            ]);
        });
    }

    /**
     * Tarifa de la pasarela para la venta de una tienda en UNA transaccion:
     * porcentaje sobre su venta + el fijo del pago repartido entre las tiendas de ese pago.
     */
    public static function tarifaPasarela(string $metodo, float $ventaTienda, int $tiendasEnPago = 1): float
    {
        $config = (array) config('monetizacion.pasarela', []);

        if (! in_array($metodo, (array) ($config['metodos'] ?? ['tarjeta']), true) || $ventaTienda <= 0) {
            return 0.0;
        }

        $porcentaje = $ventaTienda * ((float) ($config['porcentaje'] ?? 0) / 100);
        $fijo = (float) ($config['fijo'] ?? 0) / max(1, $tiendasEnPago);

        return round($porcentaje + $fijo, 2);
    }

    /**
     * Crea un pedido base.
     *
     * @param  array<string, float>  $totals
     * @param  array<string, mixed>  $data
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
            'descuento_promociones' => $totals['descuento_promociones'] ?? 0,
            'total' => $totals['total'],
            'propina' => $totals['propina'] ?? 0,
            'estado' => 'pendiente',
            'metodo_pago' => $data['metodo_pago'],
            'estado_pago' => 'pendiente',
            'facturacion_tipo' => $data['facturacion_tipo'] ?? 'cf',
            'facturacion_nombre' => $data['facturacion_nombre'] ?? null,
            'facturacion_nit' => $data['facturacion_nit'] ?? null,
            'facturacion_email' => $data['facturacion_email'] ?? null,
            'notas' => $data['notas'] ?? null,
            'tipo_entrega' => $data['tipo_entrega'] ?? 'domicilio',
        ]);
    }

    /**
     * Crea items del pedido con snapshot de precio.
     *
     * @param  Collection<int, mixed>  $items
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
                'impuestos' => Iva::incluido($subtotal),
            ]);
        }
    }

    /**
     * Calcula totales para un grupo de items.
     *
     * @param  Collection<int, mixed>  $items
     * @return array<string, float>
     */
    private function totalsForItems(
        Collection $items,
        float $envio,
        float $descuentoGlobal = 0,
        float $subtotalGlobal = 0,
        float $descuentoPromociones = 0
    ): array {
        $subtotal = $items->sum(function ($item): float {
            return (float) ($item->producto->precio_oferta ?? $item->producto->precio_base) * (int) $item->cantidad;
        });
        $subtotalConPromo = max(0, $subtotal - $descuentoPromociones);
        /* Cupon y primera compra se reparten segun el peso de cada comercio en la compra. */
        $descuento = $subtotalGlobal > 0
            ? round(($subtotalConPromo / $subtotalGlobal) * $descuentoGlobal, 2)
            : 0.0;
        $baseImponible = max(0, $subtotalConPromo - $descuento);
        /* IVA incluido en el precio: se desglosa, no se suma. */
        $impuestos = Iva::incluido($baseImponible);

        return [
            'subtotal' => round($subtotal, 2),
            'envio' => round($envio, 2),
            'impuestos' => $impuestos,
            'descuento' => $descuento,
            'descuento_promociones' => round($descuentoPromociones, 2),
            'total' => round($baseImponible + $envio, 2),
        ];
    }

    /**
     * Descuento que le toca a una tienda: el cupón completo si es suyo (ninguno si es de otra)
     * y su parte proporcional de los descuentos generales.
     */
    private function descuentoDeTienda(array $totals, int $vendorId, Collection $vendorItems, float $promoVendedor): float
    {
        $subtotalTienda = $this->subtotalConPromo($vendorItems, $promoVendedor);
        $subtotalGlobal = max(0, (float) ($totals['subtotal'] ?? 0) - (float) ($totals['descuento_promociones'] ?? 0));
        $proporcion = $subtotalGlobal > 0 ? $subtotalTienda / $subtotalGlobal : 0.0;

        $cupon = (float) ($totals['descuento_cupon'] ?? 0);
        $duenaCupon = $totals['cupon_vendor_id'] ?? null;
        $general = max(0, (float) ($totals['descuento'] ?? 0) - $cupon);

        $deCupon = $duenaCupon === null ? $cupon * $proporcion : ((int) $duenaCupon === $vendorId ? $cupon : 0.0);

        return round(min($subtotalTienda, $deCupon + $general * $proporcion), 2);
    }

    private function subtotalConPromo(Collection $vendorItems, float $promoVendedor): float
    {
        $subtotal = $vendorItems->sum(fn ($item): float => (float) ($item->producto->precio_oferta ?? $item->producto->precio_base) * (int) $item->cantidad);

        return round(max(0, $subtotal - $promoVendedor), 2);
    }

    /**
     * Genera numero humano de pedido.
     */
    private function numeroPedido(): string
    {
        return 'ATL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
