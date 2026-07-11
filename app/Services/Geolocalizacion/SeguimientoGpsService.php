<?php

namespace App\Services\Geolocalizacion;

use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\DeliveryRoute;
use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de tracking GPS de repartidores.
 */
class SeguimientoGpsService
{
    /**
     * Registra una ubicacion GPS enviada por el repartidor.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeLocation(User $repartidor, array $data): MarketCourierStatus
    {
        $status = DB::transaction(function () use ($repartidor, $data): MarketCourierStatus {
            $status = MarketCourierStatus::query()->create([
                'repartidor_id' => $repartidor->id,
                'pedido_id' => $data['pedido_id'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'timestamp_gps' => $data['timestamp_gps'] ?? now(),
                'estado' => $data['estado'] ?? 'en_ruta',
                'battery_level' => $data['battery_level'] ?? null,
                'accuracy_meters' => $data['accuracy_meters'] ?? null,
                'notas' => $data['notas'] ?? null,
            ]);

            $this->appendRutaReal($status);

            return $status->refresh();
        });

        ProcesarDespachoAutomatico::dispatch();

        return $status;
    }

    /**
     * Obtiene ultima ubicacion del repartidor.
     */
    public function latestForCourier(User $repartidor): ?MarketCourierStatus
    {
        return MarketCourierStatus::query()
            ->forRepartidor($repartidor->id)
            ->latestGps()
            ->first();
    }

    /**
     * Obtiene historial GPS de un pedido.
     *
     * @return Collection<int, MarketCourierStatus>
     */
    public function historyForPedido(Pedido $pedido)
    {
        return MarketCourierStatus::query()
            ->forPedido($pedido->id)
            ->orderBy('timestamp_gps')
            ->get();
    }

    /**
     * Agrega punto GPS a la ruta real asociada.
     */
    private function appendRutaReal(MarketCourierStatus $status): void
    {
        if ($status->pedido_id === null) {
            return;
        }

        $route = DeliveryRoute::query()->where('pedido_id', $status->pedido_id)->lockForUpdate()->first();

        if ($route === null) {
            return;
        }

        $rutaReal = $route->ruta_real ?? [];
        $rutaReal[] = [
            'latitude' => (float) $status->latitude,
            'longitude' => (float) $status->longitude,
            'timestamp_gps' => $status->timestamp_gps?->toIso8601String(),
            'estado' => $status->estado,
        ];

        $route->update([
            'ruta_real' => $rutaReal,
            'estado' => $route->estado === 'asignada' ? 'iniciada' : $route->estado,
            'iniciada_at' => $route->iniciada_at ?? now(),
        ]);
    }
}
