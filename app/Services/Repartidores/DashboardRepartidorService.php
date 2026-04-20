<?php

namespace App\Services\Repartidores;

use App\Models\DeliveryRoute;
use App\Models\User;

/**
 * Servicio de metricas del repartidor.
 */
class DashboardRepartidorService
{
    /**
     * Devuelve resumen de entregas.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        return [
            'rutas_activas' => DeliveryRoute::query()->where('repartidor_id', $user->id)->activas()->count(),
            'entregas_hoy' => DeliveryRoute::query()
                ->where('repartidor_id', $user->id)
                ->whereDate('completada_at', today())
                ->count(),
        ];
    }
}

