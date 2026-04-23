<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\PedidoHistorialEstado;
use App\Models\User;
use App\Notifications\PedidoConfirmadoNotification;
use App\Notifications\PedidoEnCaminoNotification;
use App\Notifications\PedidoEntregadoNotification;
use App\Services\Fidelizacion\PuntosService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para transiciones de estado de pedidos.
 */
class EstadoPedidoService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly PuntosService $puntosService)
    {
    }

    /**
     * Registra un estado y actualiza el pedido.
     *
     * @param Pedido $pedido
     * @param string $estado
     * @param string|null $notas
     * @param User|null $usuario
     * @return Pedido
     */
    public function registrar(Pedido $pedido, string|EstadoPedido $estado, ?string $notas = null, ?User $usuario = null): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado, $notas, $usuario): Pedido {
            $estadoAnterior = $pedido->estadoValor();
            $estadoValue = $estado instanceof EstadoPedido ? $estado->value : $estado;

            $pedido->update($this->payloadEstado($estado));

            PedidoEstado::query()->create([
                'pedido_id' => $pedido->id,
                'estado' => $estadoValue,
                'notas' => $notas,
                'usuario_id' => $usuario?->id,
            ]);

            PedidoHistorialEstado::query()->create([
                'pedido_id' => $pedido->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoValue,
                'usuario_id' => $usuario?->id,
                'nota' => $notas,
            ]);

            $pedido = $pedido->refresh();
            $this->notificarCambio($pedido, $estadoValue);

            if ($estadoValue === EstadoPedido::Entregado->value) {
                $this->puntosService->otorgarPorPedido($pedido);
            }

            return $pedido;
        });
    }

    /**
     * Marca un pedido como pagado.
     *
     * @param Pedido $pedido
     * @return Pedido
     */
    public function marcarPagado(Pedido $pedido): Pedido
    {
        $pedido->update(['estado_pago' => 'pagado']);

        return $pedido->refresh();
    }

    /**
     * Construye payload de estado.
     *
     * @param string $estado
     * @return array<string, mixed>
     */
    private function payloadEstado(string|EstadoPedido $estado): array
    {
        $estadoValue = $estado instanceof EstadoPedido ? $estado->value : $estado;

        return match ($estadoValue) {
            EstadoPedido::Confirmado->value => ['estado' => $estadoValue, 'confirmado_at' => now()],
            EstadoPedido::Cancelado->value => ['estado' => $estadoValue, 'cancelado_at' => now()],
            default => ['estado' => $estadoValue],
        };
    }

    /**
     * Dispara notificaciones in-app segun el estado alcanzado.
     *
     * @param Pedido $pedido
     * @param string $estado
     * @return void
     */
    private function notificarCambio(Pedido $pedido, string $estado): void
    {
        $pedido->loadMissing('cliente');

        if ($pedido->cliente === null) {
            return;
        }

        match ($estado) {
            EstadoPedido::Confirmado->value => $pedido->cliente->notify(new PedidoConfirmadoNotification($pedido)),
            EstadoPedido::EnRuta->value => $pedido->cliente->notify(new PedidoEnCaminoNotification($pedido)),
            EstadoPedido::Entregado->value => $pedido->cliente->notify(new PedidoEntregadoNotification($pedido)),
            default => null,
        };
    }
}
