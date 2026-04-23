<?php

namespace App\Services\Clientes;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de perfil del cliente.
 */
class PerfilClienteService
{
    /**
     * Devuelve datos del perfil.
     */
    public function detail(User $user): User
    {
        return $user->load(['clienteDetalle', 'direcciones', 'puntosCliente']);
    }

    /**
     * Actualiza perfil del cliente.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update(collect($data)->only(['name', 'phone'])->all());

            $detalle = collect($data)->only(['dpi', 'telefono', 'fecha_nacimiento', 'preferencias'])->all();
            if ($detalle !== []) {
                $user->clienteDetalle()->updateOrCreate(['user_id' => $user->id], $detalle);
            }

            return $user->refresh()->load('clienteDetalle');
        });
    }
}
