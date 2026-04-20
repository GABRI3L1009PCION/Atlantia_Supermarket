<?php

namespace App\Observers;

use App\Events\PedidoCreado;
use App\Events\PedidoEntregado;
use App\Jobs\Ml\DetectarFraudeEnPedido;
use App\Models\Pedido;
use Illuminate\Support\Str;

/**
 * Observer de pedidos para eventos de dominio.
 */
class PedidoObserver
{
    /**
     * Asigna UUID y numero humano si faltan.
     *
     * @param Pedido $pedido
     * @return void
     */
    public function creating(Pedido $pedido): void
    {
        if (empty($pedido->uuid)) {
            $pedido->uuid = (string) Str::uuid();
        }

        if (empty($pedido->numero_pedido)) {
            $pedido->numero_pedido = 'ATL-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        }
    }

    /**
     * Emite evento de pedido creado.
     *
     * @param Pedido $pedido
     * @return void
     */
    public function created(Pedido $pedido): void
    {
        if ($pedido->pedido_padre_id === null) {
            PedidoCreado::dispatch($pedido);
            DetectarFraudeEnPedido::dispatch($pedido->id);
        }
    }

    /**
     * Emite evento cuando un pedido cambia a entregado.
     *
     * @param Pedido $pedido
     * @return void
     */
    public function updated(Pedido $pedido): void
    {
        if ($pedido->wasChanged('estado') && $pedido->estado === 'entregado') {
            PedidoEntregado::dispatch($pedido);
        }
    }
}
