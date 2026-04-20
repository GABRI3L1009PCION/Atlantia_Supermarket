<?php

namespace App\Services\Geolocalizacion;

use App\Models\DeliveryZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return DeliveryZone::query()->orderBy('municipio')->orderBy('nombre')->paginate(25)->withQueryString();
    }

    /**
     * Crea zona global.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): DeliveryZone
    {
        return DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'slug' => $data['slug'] ?? Str::slug((string) $data['nombre']),
            ...$data,
        ]);
    }

    /**
     * Actualiza zona global.
     *
     * @param array<string, mixed> $data
     */
    public function update(DeliveryZone $zone, array $data): DeliveryZone
    {
        $zone->update($data);

        return $zone->refresh();
    }
}

