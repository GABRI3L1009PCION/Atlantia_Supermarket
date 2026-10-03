<?php

namespace App\Observers;

use App\Enums\EstadoPedido;
use App\Events\PedidoCreado;
use App\Events\PedidoEntregado;
use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\Pedido;
use Illuminate\Support\Str;

/**
 * Observer de pedidos para eventos de dominio.
 */
class PedidoObserver
{
    /**
     * Asigna UUID y numero humano si faltan.
     */
    public function creating(Pedido $pedido): void
    {
        if (empty($pedido->uuid)) {
            $pedido->uuid = (string) Str::uuid();
        }

        if (empty($pedido->numero_pedido)) {
            $pedido->numero_pedido = 'ATL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        }
    }

    /**
     * Emite evento de pedido creado.
     */
    public function created(Pedido $pedido): void
    {
        if ($pedido->vendor_id !== null) {
            PedidoCreado::dispatch($pedido);
        }

        if (in_array($pedido->estadoValor(), [
            EstadoPedido::Confirmado->value,
            EstadoPedido::EnPreparacion->value,
            EstadoPedido::ListoParaEntrega->value,
        ], true)) {
            ProcesarDespachoAutomatico::dispatch($pedido->id);
        }
    }

    /**
     * Emite evento cuando un pedido cambia a entregado.
     */
    public function updated(Pedido $pedido): void
    {
        if ($pedido->wasChanged('estado') && in_array($pedido->estadoValor(), [
            EstadoPedido::Confirmado->value,
            EstadoPedido::EnPreparacion->value,
            EstadoPedido::ListoParaEntrega->value,
        ], true)) {
            ProcesarDespachoAutomatico::dispatch($pedido->id);
        }

        if ($pedido->wasChanged('estado') && $pedido->estado === EstadoPedido::Entregado) {
            PedidoEntregado::dispatch($pedido);
        }
    }
}
