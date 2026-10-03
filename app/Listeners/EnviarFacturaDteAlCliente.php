<?php

namespace App\Listeners;

use App\Events\DteEmitido;
use App\Jobs\EnviarCorreoFactura;
use App\Services\Fel\DteComprobantePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Genera el comprobante PDF y agenda el envio al cliente.
 */
class EnviarFacturaDteAlCliente implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa el DTE emitido.
     */
    public function handle(DteEmitido $event): void
    {
        $dte = $event->dte->load(['vendor.fiscalProfile', 'pedido.cliente', 'items.producto']);

        app(DteComprobantePdf::class)->store($dte);

        EnviarCorreoFactura::dispatch($dte->id);
    }
}
