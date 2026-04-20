<?php

namespace App\Services\Resenas;

use App\Models\Pedido;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de resenas del cliente.
 */
class ResenaService
{
    /**
     * Lista resenas del cliente.
     *
     * @return Collection<int, Resena>
     */
    public function forUser(User $user): Collection
    {
        return Resena::query()->with(['producto'])->where('cliente_id', $user->id)->latest()->get();
    }

    /**
     * Crea resena para producto comprado.
     *
     * @param array<string, mixed> $data
     */
    public function create(Pedido $pedido, array $data, User $user): Resena
    {
        return DB::transaction(function () use ($pedido, $data, $user): Resena {
            $productoId = (int) $data['producto_id'];

            $pedido->items()->where('producto_id', $productoId)->firstOrFail();

            return Resena::query()->create([
                'uuid' => (string) Str::uuid(),
                'producto_id' => $productoId,
                'cliente_id' => $user->id,
                'pedido_id' => $pedido->id,
                'calificacion' => $data['calificacion'],
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'],
                'imagenes_count' => 0,
                'aprobada' => false,
                'flagged_ml' => false,
            ]);
        });
    }

    /**
     * Elimina una resena propia.
     */
    public function delete(Resena $resena): void
    {
        $resena->delete();
    }
}

