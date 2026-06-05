<?php

namespace App\Listeners;

use App\Events\PedidoCreado;
use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Exceptions\DteCertificadorException;
use App\Jobs\EnviarDteAlCertificador;
use App\Models\Pedido;
use App\Services\Fel\DteGeneradorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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
            if ($this->debeOmitirFacturacionAutomatica($pedidoHijo)) {
                continue;
            }

            try {
                $dte = $dteGeneradorService->emitirParaPedido($pedidoHijo);
                EnviarDteAlCertificador::dispatch($dte->id);
            } catch (DteCertificadorException $exception) {
                Log::warning('No fue posible emitir DTE automatico para el pedido.', [
                    'pedido_id' => $pedidoHijo->id,
                    'vendor_id' => $pedidoHijo->vendor_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Los pagos con tarjeta se facturan inmediatamente al aprobarse el cobro.
     */
    private function debeOmitirFacturacionAutomatica(Pedido $pedido): bool
    {
        if ($pedido->dte_id !== null) {
            return true;
        }

        if ($pedido->metodo_pago === MetodoPago::Tarjeta && $pedido->estado_pago !== EstadoPago::Pagado) {
            return true;
        }

        return false;
    }
}
