<?php

namespace App\Services\Geolocalizacion;

use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de zonas de entrega por vendedor.
 */
class VendorZonaEntregaService
{
    /**
     * Devuelve zonas disponibles y seleccionadas del vendedor.
     *
     * @return array<string, mixed>
     */
    public function forVendor(User $user): array
    {
        $vendor = $user->vendor;
        $disponibles = DeliveryZone::query()->active()->orderBy('nombre')->get();
        $seleccionadas = $vendor?->deliveryZones()->get() ?? new Collection();
        $configuradas = $seleccionadas->keyBy('id');
        $zonas = $disponibles->map(function (DeliveryZone $zone) use ($configuradas): array {
            $selected = $configuradas->get($zone->id);
            $cost = $selected?->pivot?->costo_override ?? $zone->costo_base;
            $time = $selected?->pivot?->tiempo_estimado_min ?? 30;
            $active = (bool) ($selected?->pivot?->activa ?? false);

            return [
                'id' => $zone->id,
                'nombre' => $zone->nombre,
                'municipio' => $zone->municipio,
                'descripcion' => $zone->descripcion,
                'costo_base' => (float) $zone->costo_base,
                'costo_envio' => (float) $cost,
                'tiempo_estimado_min' => (int) $time,
                'activa' => $active,
                'configurada' => $selected !== null,
                'radio_km' => $this->radiusKm($zone),
            ];
        });

        $activeCount = $zonas->where('activa', true)->count();
        $total = max(1, $zonas->count());

        return [
            'disponibles' => $disponibles,
            'seleccionadas' => $seleccionadas,
            'zonas' => $zonas,
            'resumen' => [
                'disponibles' => $zonas->count(),
                'activas' => $activeCount,
                'cobertura' => (int) round(($activeCount / $total) * 100),
            ],
        ];
    }

    /**
     * Sincroniza zonas del vendedor.
     *
     * @param array<string, mixed> $data
     */
    public function sync(User $user, array $data): void
    {
        $sync = collect($data['zonas'] ?? [])->mapWithKeys(function (array $zona): array {
            return [
                (int) $zona['delivery_zone_id'] => [
                    'costo_override' => $zona['costo_override'] ?? null,
                    'tiempo_estimado_min' => $zona['tiempo_estimado_min'] ?? 45,
                    'activa' => $zona['activa'] ?? true,
                ],
            ];
        })->all();

        $user->vendor?->deliveryZones()->sync($sync);
    }

    private function radiusKm(DeliveryZone $zone): float
    {
        $radius = $zone->poligono_geojson['metadata']['radio_km'] ?? null;

        return is_numeric($radius) && (float) $radius > 0 ? (float) $radius : 3.5;
    }
}
