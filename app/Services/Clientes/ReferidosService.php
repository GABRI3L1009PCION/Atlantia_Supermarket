<?php

namespace App\Services\Clientes;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Invita a un amigo": cuando el invitado recibe su primer pedido, los dos ganan saldo.
 */
class ReferidosService
{
    public function __construct(private readonly SaldoService $saldoService) {}

    public static function recompensa(): float
    {
        return (float) config('atlantia.referidos.recompensa', 15);
    }

    public function codigoDe(User $user): string
    {
        if (filled($user->codigo_referido)) {
            return $user->codigo_referido;
        }

        do {
            $codigo = Str::upper(Str::substr(Str::slug(Str::before($user->name, ' ')), 0, 6)).random_int(100, 999);
            $codigo = preg_replace('/[^A-Z0-9]/', '', $codigo) ?: 'ATL'.random_int(1000, 9999);
        } while (User::query()->where('codigo_referido', $codigo)->exists());

        $user->forceFill(['codigo_referido' => $codigo])->save();

        return $codigo;
    }

    /**
     * Vincula una cuenta nueva con quien la invito (una sola vez y nunca a si misma).
     */
    public function vincular(User $nuevo, ?string $codigo): void
    {
        $codigo = Str::upper(trim((string) $codigo));
        if ($codigo === '' || $nuevo->referido_por_id !== null) {
            return;
        }

        $padrino = User::query()->where('codigo_referido', $codigo)->first();
        if ($padrino === null || $padrino->is($nuevo)) {
            return;
        }

        $nuevo->forceFill(['referido_por_id' => $padrino->id])->save();
    }

    /**
     * Al entregar el primer pedido del invitado, acredita saldo a ambos.
     */
    public function recompensarPorEntrega(Pedido $pedido): void
    {
        $cliente = $pedido->cliente;
        if ($cliente === null || $cliente->referido_por_id === null || $cliente->referido_recompensado_at !== null) {
            return;
        }

        DB::transaction(function () use ($cliente, $pedido): void {
            $cliente = User::query()->lockForUpdate()->find($cliente->id);
            if ($cliente === null || $cliente->referido_recompensado_at !== null) {
                return;
            }

            $padrino = User::query()->find($cliente->referido_por_id);
            $monto = self::recompensa();

            $this->saldoService->acreditar($cliente, $monto, 'referido', 'Bienvenida por invitación de un amigo', $pedido);
            if ($padrino !== null) {
                $this->saldoService->acreditar($padrino, $monto, 'referido', 'Tu amigo '.Str::before($cliente->name, ' ').' hizo su primera compra', $pedido);
            }

            $cliente->forceFill(['referido_recompensado_at' => now()])->save();
        });
    }
}
