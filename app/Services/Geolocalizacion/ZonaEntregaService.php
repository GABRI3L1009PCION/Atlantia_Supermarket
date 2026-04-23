<?php

namespace App\Services\Geolocalizacion;

use App\Models\DeliveryZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Servicio administrativo de zonas de entrega.
 */
class ZonaEntregaService
{
    /**
     * Pagina zonas.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return DeliveryZone::query()
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('nombre', 'like', "%{$search}%")
                        ->orWhere('municipio', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('activa')
            ->orderBy('municipio')
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Devuelve zonas activas cacheadas para checkout y calculo de cobertura.
     *
     * @return Collection<int, DeliveryZone>
     */
    public function activeCached(): Collection
    {
        return Cache::remember('delivery_zones:active', now()->addHour(), function (): Collection {
            return DeliveryZone::query()
                ->where('activa', true)
                ->orderBy('municipio')
                ->orderBy('nombre')
                ->get();
        });
    }

    /**
     * Crea zona global.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): DeliveryZone
    {
        $zoneData = $this->preparePersistenceData($data);

        return DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            ...$zoneData,
        ]);
    }

    /**
     * Actualiza zona global.
     *
     * @param array<string, mixed> $data
     */
    public function update(DeliveryZone $zone, array $data): DeliveryZone
    {
        $zone->update($this->preparePersistenceData($data));

        return $zone->refresh();
    }

    /**
     * Elimina logicamente una zona global.
     */
    public function delete(DeliveryZone $zone): void
    {
        $zone->delete();
    }

    /**
     * Separa columnas reales y metadata escalable de operacion.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function preparePersistenceData(array $data): array
    {
        $metadata = [
            'barrios' => $this->splitBarrios($data['barrios'] ?? null),
            'tiempo_estimado_min' => $data['tiempo_estimado_min'] ?? null,
            'capacidad_diaria' => $data['capacidad_diaria'] ?? null,
            'envio_gratis_desde' => $data['envio_gratis_desde'] ?? null,
            'hora_apertura' => $data['hora_apertura'] ?? null,
            'hora_cierre' => $data['hora_cierre'] ?? null,
            'dias_operacion' => $data['dias_operacion'] ?? [],
            'acepta_programados' => (bool) ($data['acepta_programados'] ?? false),
            'cobro_peso_volumen' => (bool) ($data['cobro_peso_volumen'] ?? false),
        ];

        return [
            'nombre' => $data['nombre'],
            'slug' => $data['slug'] ?? Str::slug((string) $data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'municipio' => $data['municipio'],
            'costo_base' => $data['costo_base'],
            'latitude_centro' => $data['latitude_centro'] ?? null,
            'longitude_centro' => $data['longitude_centro'] ?? null,
            'poligono_geojson' => [
                'type' => 'FeatureCollection',
                'features' => [],
                'metadata' => $metadata,
            ],
            'activa' => (bool) ($data['activa'] ?? false),
        ];
    }

    /**
     * Convierte barrios en arreglo limpio.
     *
     * @param mixed $barrios
     * @return array<int, string>
     */
    private function splitBarrios(mixed $barrios): array
    {
        return collect(explode(',', (string) $barrios))
            ->map(fn (string $barrio): string => trim($barrio))
            ->filter()
            ->values()
            ->all();
    }
}
