<?php

namespace App\Services\Clientes;

use App\DTOs\DireccionDTO;
use App\Models\Cliente\Direccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        return Direccion::query()->where('user_id', $user->id)->where('borrador', false)->latest('es_principal')->latest()->get();
    }

    /**
     * Crea direccion del cliente.
     */
    public function create(User $user, DireccionDTO $direccionDTO, bool $borrador = false): Direccion
    {
        return DB::transaction(function () use ($user, $direccionDTO, $borrador): Direccion {
            $data = $direccionDTO->toArray();
            $debeSerPrincipal = ! $borrador && ($direccionDTO->esPrincipal
                || ! Direccion::query()->where('user_id', $user->id)->where('borrador', false)->exists());

            if ($debeSerPrincipal) {
                Direccion::query()->where('user_id', $user->id)->update(['es_principal' => false]);
            }

            return Direccion::query()->create([
                ...$data,
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'es_principal' => $debeSerPrincipal,
                'activa' => true,
                'borrador' => $borrador,
            ]);
        });
    }

    /**
     * Actualiza direccion.
     */
    public function update(Direccion $direccion, DireccionDTO $direccionDTO): Direccion
    {
        return DB::transaction(function () use ($direccion, $direccionDTO): Direccion {
            $data = $direccionDTO->toArray();

            if ($direccionDTO->esPrincipal) {
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
