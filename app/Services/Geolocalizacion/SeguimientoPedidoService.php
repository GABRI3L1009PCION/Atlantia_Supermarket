<?php

namespace App\Services\Geolocalizacion;

use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use Illuminate\Support\Collection;

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
        $rutaReal = $this->normalizarPuntos(collect($pedido->deliveryRoute?->ruta_real ?? []));
        $rutaPlanificada = $this->normalizarPuntos(collect($pedido->deliveryRoute?->ruta_planificada ?? []));

        return [
            'pedido' => $pedido,
            'ruta' => $pedido->deliveryRoute,
            'historial' => $pedido->estados,
            'ultima_ubicacion' => $ultimaUbicacion,
            'mapbox_token' => config('services.mapbox.token'),
            'destino' => $destino,
            'repartidor' => $this->ubicacion($ultimaUbicacion),
            'ruta_planificada' => $rutaPlanificada,
            'ruta_real' => $rutaReal,
            'centro' => $this->centro($ultimaUbicacion, $destino),
            'eta_minutos' => $pedido->deliveryRoute?->tiempo_estimado_min,
            'actualizado_at' => $ultimaUbicacion?->timestamp_gps?->toIso8601String(),
        ];
    }

    /**
     * Devuelve coordenadas del destino o una referencia municipal.
     *
     * @param Pedido $pedido
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
        ];
    }

    /**
     * Convierte una ubicacion GPS a arreglo serializable.
     *
     * @param MarketCourierStatus|null $status
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

    /**
     * Normaliza puntos de ruta para Mapbox.
     *
     * @param Collection<int, mixed> $puntos
     * @return array<int, array{latitude: float, longitude: float}>
     */
    private function normalizarPuntos(Collection $puntos): array
    {
        return $puntos
            ->map(function (mixed $punto): ?array {
                $latitude = $punto['latitude'] ?? $punto['lat'] ?? null;
                $longitude = $punto['longitude'] ?? $punto['lng'] ?? $punto['lon'] ?? null;

                if ($latitude === null || $longitude === null) {
                    return null;
                }

                return [
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Calcula centro inicial del mapa.
     *
     * @param MarketCourierStatus|null $ultimaUbicacion
     * @param array<string, mixed> $destino
     * @return array{latitude: float, longitude: float}
     */
    private function centro(?MarketCourierStatus $ultimaUbicacion, array $destino): array
    {
        if ($ultimaUbicacion !== null) {
            return [
                'latitude' => (float) $ultimaUbicacion->latitude,
                'longitude' => (float) $ultimaUbicacion->longitude,
            ];
        }

        return [
            'latitude' => (float) $destino['latitude'],
            'longitude' => (float) $destino['longitude'],
        ];
    }
}
