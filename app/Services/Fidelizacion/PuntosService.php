<?php

namespace App\Services\Fidelizacion;

use App\Models\Pedido;
use App\Models\PuntosCliente;
use App\Models\TransaccionPunto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de puntos de fidelizacion.
 */
class PuntosService
{
    /**
     * Obtiene o crea saldo del cliente.
     */
    public function saldo(User $user): PuntosCliente
    {
        return PuntosCliente::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['puntos_actuales' => 0, 'puntos_totales_ganados' => 0]
        );
    }

    /**
     * Otorga puntos por un pedido entregado.
     */
    public function otorgarPorPedido(Pedido $pedido): void
    {
        if (TransaccionPunto::query()->where('pedido_id', $pedido->id)->where('tipo', 'ganado')->exists()) {
            return;
        }

        DB::transaction(function () use ($pedido): void {
            $puntos = (int) floor(((float) $pedido->total) / 10);

            if ($puntos < 1) {
                return;
            }

            $saldo = $this->saldo($pedido->cliente);
            $saldo->increment('puntos_actuales', $puntos);
            $saldo->increment('puntos_totales_ganados', $puntos);

            TransaccionPunto::query()->create([
                'user_id' => $pedido->cliente_id,
                'tipo' => 'ganado',
                'puntos' => $puntos,
                'pedido_id' => $pedido->id,
                'descripcion' => 'Puntos acreditados por pedido entregado.',
            ]);
        });
    }
}
