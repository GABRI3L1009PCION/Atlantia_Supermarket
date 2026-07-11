<?php

namespace App\Jobs;

use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Services\Repartidores\AutomaticDispatchService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcesarDespachoAutomatico implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 55;

    public function __construct(
        public readonly ?int $pedidoId = null,
        public readonly ?int $externalOrderId = null
    ) {
        $this->onQueue('critical');
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        if ($this->pedidoId !== null) {
            return 'pedido:'.$this->pedidoId;
        }

        if ($this->externalOrderId !== null) {
            return 'external:'.$this->externalOrderId;
        }

        return 'global';
    }

    public function handle(AutomaticDispatchService $dispatchService): void
    {
        if (! config('dispatch.enabled')) {
            return;
        }

        if ($this->pedidoId !== null) {
            $pedido = Pedido::query()->find($this->pedidoId);

            if ($pedido !== null) {
                $dispatchService->dispatchPedido($pedido);
            }

            return;
        }

        if ($this->externalOrderId !== null) {
            $order = ExternalDeliveryOrder::query()->find($this->externalOrderId);

            if ($order !== null) {
                $dispatchService->dispatchExternal($order);
            }

            return;
        }

        $dispatchService->run();
    }
}
