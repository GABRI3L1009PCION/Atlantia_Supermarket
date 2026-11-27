<?php

use App\Models\PaymentSplit;
use App\Services\Pedidos\SplitMultivendedorService;
use Illuminate\Database\Migrations\Migration;

/**
 * Las ventas aun no pagadas a los vendedores se recalculan con las reglas nuevas:
 * la venta del comercio no incluye el envio y la tarifa de pasarela se aplica por transaccion.
 * Las ventas ya pagadas no se tocan.
 */
return new class extends Migration
{
    public function up(): void
    {
        PaymentSplit::query()
            ->with(['pedido', 'payment'])
            ->where('estado', 'pendiente')
            ->whereNull('liquidacion_id')
            ->whereNotNull('pedido_id')
            ->each(function (PaymentSplit $split): void {
                $pedido = $split->pedido;
                if ($pedido === null) {
                    return;
                }

                $brutoAnterior = (float) $split->monto_bruto;
                $tasa = $brutoAnterior > 0 ? (float) $split->comision_atlantia / $brutoAnterior : 0.0;
                $bruto = round(max(0, (float) $pedido->total - (float) $pedido->envio), 2);
                $comision = round($bruto * $tasa, 2);
                $tiendas = max(1, (int) PaymentSplit::query()->where('payment_id', $split->payment_id)->count());
                $metodo = (string) ($split->payment?->metodo?->value ?? $split->payment?->metodo);
                $pasarela = SplitMultivendedorService::tarifaPasarela($metodo, $bruto, $tiendas);

                $split->forceFill([
                    'monto_bruto' => $bruto,
                    'comision_atlantia' => $comision,
                    'comision_pasarela' => $pasarela,
                    'monto_neto_vendedor' => round(max(0, $bruto - $comision - $pasarela), 2),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        // Recalculo de datos: no se revierte.
    }
};
