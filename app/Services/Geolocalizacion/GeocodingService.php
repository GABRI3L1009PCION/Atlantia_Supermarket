<?php

namespace App\Services\Geolocalizacion;

use App\Exceptions\GeolocalizacionException;
use Illuminate\Support\Facades\Http;

/**
 * Servicio de geocodificacion con Mapbox.
 */
class GeocodingService
{
    /**
     * Convierte una direccion textual en coordenadas.
     *
     * @param string $direccion
     * @param string|null $municipio
     * @return array<string, mixed>
     *
     * @throws GeolocalizacionException
     */
    public function geocode(string $direccion, ?string $municipio = null): array
    {
        $query = trim($direccion . ' ' . ($municipio ?? '') . ' Izabal Guatemala');
        $token = $this->token();

        if ($this->usarMock($token)) {
            return $this->mock($query);
        }

        $response = Http::timeout(12)->get(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($query) . '.json',
            [
                'access_token' => $token,
                'country' => 'gt',
                'limit' => 1,
                'language' => 'es',
            ]
        );

        if (! $response->successful() || empty($response->json('features.0.center'))) {
            throw new GeolocalizacionException('No fue posible geocodificar la direccion.');
        }

        $feature = $response->json('features.0');

        return [
            'latitude' => (float) $feature['center'][1],
            'longitude' => (float) $feature['center'][0],
            'mapbox_place_id' => $feature['id'] ?? null,
            'place_name' => $feature['place_name'] ?? $query,
            'source' => 'mapbox',
        ];
    }

    /**
     * Convierte coordenadas en direccion aproximada.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array<string, mixed>
     */
    public function reverse(float $latitude, float $longitude): array
    {
        $token = $this->token();

        if ($this->usarMock($token)) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'place_name' => 'Izabal, Guatemala',
                'source' => 'mock',
            ];
        }

        $response = Http::timeout(12)->get(
            "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitude},{$latitude}.json",
            ['access_token' => $token, 'limit' => 1, 'language' => 'es']
        );

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'place_name' => $response->json('features.0.place_name') ?? 'Izabal, Guatemala',
            'source' => 'mapbox',
        ];
    }

    /**
     * Obtiene token de Mapbox.
     *
     * @return string|null
     */
    private function token(): ?string
    {
        return config('services.mapbox.token') ?: env('MAPBOX_TOKEN');
    }

    /**
     * Determina si debe usarse respuesta local.
     *
     * @param string|null $token
     * @return bool
     */
    private function usarMock(?string $token): bool
    {
        return empty($token) || app()->environment(['local', 'testing']);
    }

    /**
     * Coordenadas razonables dentro de Izabal para desarrollo local.
     *
     * @param string $query
     * @return array<string, mixed>
     */
    private function mock(string $query): array
    {
        $hash = abs(crc32($query));

        return [
            'latitude' => round(15.7275 + (($hash % 1000) / 100000), 8),
            'longitude' => round(-88.5944 - (($hash % 900) / 100000), 8),
            'mapbox_place_id' => 'mock.izabal.' . $hash,
            'place_name' => $query,
            'source' => 'mock',
        ];
    }
}
