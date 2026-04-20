<?php

namespace App\Services\Repartidores;

use App\Models\Pedido;
use App\Models\User;

/**
 * Servicio de incidencias de entrega.
 */
class IncidenciaService
{
    /**
     * Registra incidencia como estado historico del pedido.
     *
     * @param array<string, mixed> $data
     */
    public function store(Pedido $pedido, array $data, User $user): void
    {
        $pedido->estados()->create([
            'estado' => 'incidencia',
            'notas' => $data['descripcion'],
            'usuario_id' => $user->id,
        ]);
    }
}

