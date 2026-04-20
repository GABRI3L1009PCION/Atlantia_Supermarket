<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para transiciones de estado de pedidos.
 */
class EstadoPedidoService
{
    /**
     * Registra un estado y actualiza el pedido.
     *
     * @param Pedido $pedido
     * @param string $estado
     * @param string|null $notas
     * @param User|null $usuario
     * @return Pedido
     */
    public function registrar(Pedido $pedido, string $estado, ?string $notas = null, ?User $usuario = null): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado, $notas, $usuario): Pedido {
            $pedido->update($this->payloadEstado($estado));

            PedidoEstado::query()->create([
                'pedido_id' => $pedido->id,
                'estado' => $estado,
                'notas' => $notas,
                'usuario_id' => $usuario?->id,
            ]);

            return $pedido->refresh();
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
    private function payloadEstado(string $estado): array
    {
        return match ($estado) {
            'confirmado' => ['estado' => $estado, 'confirmado_at' => now()],
            'cancelado' => ['estado' => $estado, 'cancelado_at' => now()],
            default => ['estado' => $estado],
        };
    }
}
