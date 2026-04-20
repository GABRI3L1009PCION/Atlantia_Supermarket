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
        return [
            'disponibles' => DeliveryZone::query()->active()->orderBy('nombre')->get(),
            'seleccionadas' => $user->vendor?->deliveryZones()->get() ?? new Collection(),
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
}

