<?php

namespace App\Services\Geolocalizacion;

use App\Models\MarketCourierStatus;
use App\Models\Pedido;

/**
 * Servicio de seguimiento de pedidos.
 */
class SeguimientoPedidoService
{
    /**
     * Devuelve estado, ruta y ultima ubicacion del pedido.
     *
     * @return array<string, mixed>
     */
    public function detail(Pedido $pedido): array
    {
        $pedido->load(['deliveryRoute.repartidor', 'estados.usuario', 'direccion', 'vendor']);

        $ultimaUbicacion = $pedido->deliveryRoute?->repartidor_id
            ? MarketCourierStatus::query()
                ->where('repartidor_id', $pedido->deliveryRoute->repartidor_id)
                ->where('pedido_id', $pedido->id)
                ->latest('timestamp_gps')
                ->first()
            : null;

        $destino = $this->destino($pedido);

        /*
         * La ubicacion del repartidor solo se comparte cuando el pedido ya va hacia el cliente.
         * Antes de eso el repartidor esta en el punto de recogida, que no se muestra al cliente.
         */
        $enCamino = $pedido->estadoValor() === 'en_ruta';
        $repartidor = $enCamino ? $this->ubicacion($ultimaUbicacion) : null;

        return [
            'pedido' => $pedido,
            'ruta' => $pedido->deliveryRoute,
            'historial' => $pedido->estados,
            'ultima_ubicacion' => $enCamino ? $ultimaUbicacion : null,
            'estado' => $pedido->estadoValor(),
            'destino' => $destino,
            'repartidor' => $repartidor,
            'repartidor_nombre' => $this->nombreCorto($pedido->deliveryRoute?->repartidor?->name),
            'centro' => $repartidor ?? ['latitude' => (float) $destino['latitude'], 'longitude' => (float) $destino['longitude']],
            'eta_minutos' => $pedido->deliveryRoute?->tiempo_estimado_min,
            'actualizado_at' => $enCamino ? $ultimaUbicacion?->timestamp_gps?->toIso8601String() : null,
            'mapa' => [
                'tiles_url' => config('services.mapas.tiles_url'),
                'tiles_attribution' => config('services.mapas.tiles_attribution'),
                'routing_url' => config('services.mapas.routing_url'),
            ],
        ];
    }

    /**
     * Nombre y la inicial del apellido, por ejemplo "Carlos M.".
     */
    private function nombreCorto(?string $nombre): ?string
    {
        $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
        $partes = array_values(array_filter($partes));

        if ($partes === []) {
            return null;
        }

        return isset($partes[1]) ? $partes[0].' '.mb_strtoupper(mb_substr($partes[1], 0, 1)).'.' : $partes[0];
    }

    /**
     * Devuelve coordenadas del destino o una referencia municipal.
     *
     * @return array<string, mixed>
     */
    private function destino(Pedido $pedido): array
    {
        $direccion = $pedido->direccion;

        if ($direccion?->latitude !== null && $direccion?->longitude !== null) {
            return [
                'latitude' => (float) $direccion->latitude,
                'longitude' => (float) $direccion->longitude,
                'label' => $direccion->alias ?? 'Entrega',
                'address' => $direccion->direccion_linea_1,
                'aproximado' => false,
            ];
        }

        $municipio = (string) ($direccion?->municipio ?? 'Puerto Barrios');
        $referencias = [
            'Puerto Barrios' => ['latitude' => 15.7309, 'longitude' => -88.5944],
            'Santo Tomas' => ['latitude' => 15.6968, 'longitude' => -88.6166],
            'Morales' => ['latitude' => 15.4769, 'longitude' => -88.8166],
            'Los Amates' => ['latitude' => 15.2558, 'longitude' => -89.0964],
            'Livingston' => ['latitude' => 15.8277, 'longitude' => -88.7501],
            'El Estor' => ['latitude' => 15.5333, 'longitude' => -89.3500],
        ];

        return [
            ...($referencias[$municipio] ?? $referencias['Puerto Barrios']),
            'label' => $municipio,
            'address' => $direccion?->direccion_linea_1,
            'aproximado' => true,
        ];
    }

    /**
     * Convierte una ubicacion GPS a arreglo serializable.
     *
     * @return array<string, mixed>|null
     */
    private function ubicacion(?MarketCourierStatus $status): ?array
    {
        if ($status === null) {
            return null;
        }

        return [
            'latitude' => (float) $status->latitude,
            'longitude' => (float) $status->longitude,
            'estado' => $status->estado,
            'timestamp_gps' => $status->timestamp_gps?->toIso8601String(),
            'accuracy_meters' => $status->accuracy_meters === null ? null : (float) $status->accuracy_meters,
        ];
    }
}
