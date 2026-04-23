<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Inventario\StockService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Servicio de cancelacion de pedidos.
 */
class CancelacionService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly EstadoPedidoService $estadoPedidoService,
        private readonly StockService $stockService
    ) {
    }

    /**
     * Cancela un pedido y libera inventario reservado.
     *
     * @param Pedido $pedido
     * @param User $usuario
     * @param string $motivo
     * @return Pedido
     *
     * @throws TransaccionFallidaException
     */
    public function cancelar(Pedido $pedido, User $usuario, string $motivo): Pedido
    {
        try {
            return DB::transaction(function () use ($pedido, $usuario, $motivo): Pedido {
                if (in_array($pedido->estado, [EstadoPedido::Entregado, EstadoPedido::Cancelado], true)) {
                    throw new TransaccionFallidaException('El pedido no puede cancelarse en su estado actual.');
                }

                $pedido->loadMissing('items');
                $this->stockService->releaseForPedido($pedido);

                return $this->estadoPedidoService->registrar($pedido, EstadoPedido::Cancelado, $motivo, $usuario);
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible cancelar el pedido.', previous: $exception);
        }
    }
}
