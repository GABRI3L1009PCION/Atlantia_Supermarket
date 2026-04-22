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
            'overview' => [
                'rutas_activas' => DeliveryRoute::query()->where('repartidor_id', $user->id)->activas()->count(),
                'entregas_hoy' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->whereDate('completada_at', today())
                    ->count(),
                'pendientes' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->whereIn('estado', ['pendiente', 'asignada'])
                    ->count(),
                'en_ruta' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->where('estado', 'iniciada')
                    ->count(),
            ],
            'ruta_actual' => DeliveryRoute::query()
                ->with(['pedido.cliente', 'pedido.direccion', 'pedido.items.producto'])
                ->where('repartidor_id', $user->id)
                ->whereIn('estado', ['asignada', 'iniciada', 'pausada'])
                ->orderByRaw("CASE estado WHEN 'iniciada' THEN 0 WHEN 'asignada' THEN 1 ELSE 2 END")
                ->oldest('asignada_at')
                ->first(),
            'proximas_entregas' => DeliveryRoute::query()
                ->with(['pedido.cliente', 'pedido.direccion'])
                ->where('repartidor_id', $user->id)
                ->whereIn('estado', ['asignada', 'pendiente'])
                ->latest('asignada_at')
                ->limit(4)
                ->get(),
            'rutas_recientes' => DeliveryRoute::query()
                ->with('pedido.cliente')
                ->where('repartidor_id', $user->id)
                ->latest()
                ->limit(6)
                ->get(),
            'quick_links' => [
                ['title' => 'Entregas', 'description' => 'Consulta pedidos asignados y estados.', 'route' => route('repartidor.pedidos.index')],
                ['title' => 'Rutas', 'description' => 'Revisa rutas, distancia y tiempos.', 'route' => route('repartidor.rutas.index')],
            ],
        ];
    }
}
