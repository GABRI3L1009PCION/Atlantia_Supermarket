<?php

namespace App\Services\Clientes;

use App\Models\Cliente\Direccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de direcciones de entrega.
 */
class DireccionService
{
    /**
     * Lista direcciones del cliente.
     *
     * @return Collection<int, Direccion>
     */
    public function forUser(User $user): Collection
    {
        return Direccion::query()->where('user_id', $user->id)->latest('es_principal')->latest()->get();
    }

    /**
     * Crea direccion del cliente.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Direccion
    {
        return DB::transaction(function () use ($user, $data): Direccion {
            if ($data['es_principal'] ?? false) {
                Direccion::query()->where('user_id', $user->id)->update(['es_principal' => false]);
            }

            return Direccion::query()->create([...$data, 'user_id' => $user->id]);
        });
    }

    /**
     * Actualiza direccion.
     *
     * @param array<string, mixed> $data
     */
    public function update(Direccion $direccion, array $data): Direccion
    {
        return DB::transaction(function () use ($direccion, $data): Direccion {
            if ($data['es_principal'] ?? false) {
                Direccion::query()->where('user_id', $direccion->user_id)->update(['es_principal' => false]);
            }

            $direccion->update($data);

            return $direccion->refresh();
        });
    }

    /**
     * Elimina direccion.
     */
    public function delete(Direccion $direccion): void
    {
        $direccion->delete();
    }
}

