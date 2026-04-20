<?php

namespace App\Listeners;

use App\Events\PedidoCreado;
use App\Jobs\EnviarDteAlCertificador;
use App\Models\Pedido;
use App\Services\Fel\DteGeneradorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Genera y envia DTE despues de crear un pedido.
 */
class EmitirDteTrasPedido implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa el evento de pedido creado.
     *
     * @param PedidoCreado $event
     * @return void
     */
    public function handle(PedidoCreado $event): void
    {
        $dteGeneradorService = app(DteGeneradorService::class);
        $pedido = Pedido::query()->with('pedidosHijos')->findOrFail($event->pedido->id);
        $pedidosAFacturar = $pedido->pedidosHijos->isNotEmpty() ? $pedido->pedidosHijos : collect([$pedido]);

        foreach ($pedidosAFacturar as $pedidoHijo) {
            $dte = $dteGeneradorService->emitirParaPedido($pedidoHijo);
            EnviarDteAlCertificador::dispatch($dte->id);
        }
    }
}
