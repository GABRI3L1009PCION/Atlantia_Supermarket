<?php

namespace App\Services\Clientes;

use App\Models\Pedido;
use App\Models\SaldoMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Saldo a favor del cliente. Se usa como forma de pago: la factura conserva el total
 * de la venta y el saldo solo reduce lo que el cliente paga.
 */
class SaldoService
{
    public function disponible(User $user): float
    {
        return round((float) SaldoMovimiento::query()->where('user_id', $user->id)->sum('monto'), 2);
    }

    public function acreditar(User $user, float $monto, string $motivo, string $descripcion, ?Pedido $pedido = null, ?User $autor = null): SaldoMovimiento
    {
        if ($monto <= 0) {
            throw new RuntimeException('El monto a acreditar debe ser mayor a cero.');
        }

        return SaldoMovimiento::query()->create([
            'user_id' => $user->id,
            'pedido_id' => $pedido?->id,
            'monto' => round($monto, 2),
            'motivo' => $motivo,
            'descripcion' => $descripcion,
            'creado_por' => $autor?->id,
        ]);
    }

    /**
     * Descuenta el saldo usado en un pedido. Bloquea los movimientos del cliente para
     * que dos compras simultaneas no usen el mismo saldo.
     */
    public function aplicarAPedido(User $user, Pedido $pedido, float $monto): float
    {
        if ($monto <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($user, $pedido, $monto): float {
            SaldoMovimiento::query()->where('user_id', $user->id)->lockForUpdate()->get(['id']);
            $usar = round(min($monto, $this->disponible($user)), 2);

            if ($usar <= 0) {
                return 0.0;
            }

            SaldoMovimiento::query()->create([
                'user_id' => $user->id,
                'pedido_id' => $pedido->id,
                'monto' => -$usar,
                'motivo' => 'compra',
                'descripcion' => 'Pago del pedido '.$pedido->numero_pedido,
            ]);

            return $usar;
        });
    }

    /**
     * Devuelve el saldo usado si el pedido se cancela o el pago falla.
     */
    public function revertirPedido(Pedido $pedido): void
    {
        $usado = -1 * (float) SaldoMovimiento::query()
            ->where('pedido_id', $pedido->id)
            ->where('motivo', 'compra')
            ->sum('monto');
        $devuelto = (float) SaldoMovimiento::query()
            ->where('pedido_id', $pedido->id)
            ->where('motivo', 'reintegro_compra')
            ->sum('monto');

        $pendiente = round($usado - $devuelto, 2);
        if ($pendiente > 0 && $pedido->cliente !== null) {
            $this->acreditar($pedido->cliente, $pendiente, 'reintegro_compra', 'Devolución de saldo del pedido '.$pedido->numero_pedido, $pedido);
        }
    }
}
