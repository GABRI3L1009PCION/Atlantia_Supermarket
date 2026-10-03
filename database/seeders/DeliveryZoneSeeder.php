<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder de zonas de entrega reales de Izabal.
 */
class DeliveryZoneSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de zonas.
     */
    public function run(): void
    {
        $zones = [
            [
                'nombre' => 'Puerto Barrios Centro',
                'slug' => 'puerto-barrios-centro',
                'municipio' => 'Puerto Barrios',
                'costo_base' => 18.00,
                'latitude_centro' => 15.73090000,
                'longitude_centro' => -88.59440000,
            ],
            [
                'nombre' => 'Santo Tomas de Castilla',
                'slug' => 'santo-tomas-de-castilla',
                'municipio' => 'Santo Tomas',
                'costo_base' => 20.00,
                'latitude_centro' => 15.69690000,
                'longitude_centro' => -88.62060000,
            ],
            [
                'nombre' => 'Morales casco urbano',
                'slug' => 'morales-casco-urbano',
                'municipio' => 'Morales',
                'costo_base' => 35.00,
                'latitude_centro' => 15.47250000,
                'longitude_centro' => -88.84090000,
            ],
            [
                'nombre' => 'Los Amates centro',
                'slug' => 'los-amates-centro',
                'municipio' => 'Los Amates',
                'costo_base' => 40.00,
                'latitude_centro' => 15.25660000,
                'longitude_centro' => -89.09730000,
            ],
            [
                'nombre' => 'Livingston muelle municipal',
                'slug' => 'livingston-muelle-municipal',
                'municipio' => 'Livingston',
                'costo_base' => 45.00,
                'latitude_centro' => 15.82830000,
                'longitude_centro' => -88.75060000,
            ],
            [
                'nombre' => 'El Estor centro',
                'slug' => 'el-estor-centro',
                'municipio' => 'El Estor',
                'costo_base' => 50.00,
                'latitude_centro' => 15.53330000,
                'longitude_centro' => -89.35000000,
            ],
        ];

        foreach ($zones as $zone) {
            $deliveryZone = DeliveryZone::query()->firstOrNew(['slug' => $zone['slug']]);
            $deliveryZone->fill([
                'uuid' => $deliveryZone->uuid ?? (string) Str::uuid(),
                'nombre' => $zone['nombre'],
                'descripcion' => 'Zona de cobertura para entregas de Atlantia Supermarket en Izabal.',
                'municipio' => $zone['municipio'],
                'costo_base' => $zone['costo_base'],
                'latitude_centro' => $zone['latitude_centro'],
                'longitude_centro' => $zone['longitude_centro'],
                'poligono_geojson' => null,
                'activa' => true,
            ]);
            $deliveryZone->save();
        }
    }
}
