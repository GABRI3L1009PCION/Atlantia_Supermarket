<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Services\Inventario\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Consume stock reservado despues de pago confirmado.
 */
class ActualizarInventarioPostPago implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'inventory';

    /**
     * Crea el job.
     *
     * @param int $pedidoId
     */
    public function __construct(private readonly int $pedidoId)
    {
    }

    /**
     * Consume inventario reservado del pedido.
     *
     * @param StockService $stockService
     * @return void
     */
    public function handle(StockService $stockService): void
    {
        $pedido = Pedido::query()->with('items.producto')->findOrFail($this->pedidoId);

        $stockService->consumeReservedForPedido($pedido);
    }
}
