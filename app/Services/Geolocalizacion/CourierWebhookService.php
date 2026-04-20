<?php

namespace App\Services\Geolocalizacion;

use App\Models\DeliveryRoute;

/**
 * Servicio para eventos recibidos de courier externo.
 */
class CourierWebhookService
{
    /**
     * Procesa evento externo de courier.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data, array $headers = []): array
    {
        $route = DeliveryRoute::query()->where('uuid', $data['route_uuid'] ?? null)->first();

        if ($route !== null && isset($data['estado'])) {
            $route->update([
                'estado' => $data['estado'],
                'ruta_real' => $data['ruta_real'] ?? $route->ruta_real,
            ]);
        }

        return ['processed' => true, 'route_id' => $route?->id];
    }
}
