<?php

namespace App\Services\Geolocalizacion;

use App\Models\Cliente\Direccion;
use App\Models\DeliveryZone;
use App\Models\VendorDeliveryZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resuelve cobertura activa de entrega para checkout y resumen.
 */
class DeliveryCoverageService
{
    /**
     * Radio operativo usado cuando una zona aun no tiene poligono dibujado.
     */
    private const DEFAULT_ZONE_RADIUS_KM = 8.0;

    /**
     * Municipios operativos en la fase actual.
     *
     * @var array<string, string>
     */
    private const SUPPORTED_MUNICIPIOS = [
        'puerto-barrios' => 'Puerto Barrios',
        'santo-tomas' => 'Santo Tomas',
    ];

    public function __construct(private readonly ZonaEntregaService $zonaEntregaService)
    {
    }

    /**
     * Busca la zona activa que cubre una direccion.
     */
    public function findActiveZoneFor(Direccion $direccion): ?DeliveryZone
    {
        $canonicalMunicipio = $this->canonicalMunicipio($direccion->municipio);

        if ($canonicalMunicipio === null || ! array_key_exists($canonicalMunicipio, self::SUPPORTED_MUNICIPIOS)) {
            return null;
        }

        $zones = $this->activeZones()
            ->filter(fn (DeliveryZone $zone): bool => $this->canonicalMunicipio($zone->municipio) === $canonicalMunicipio)
            ->sortBy(fn (DeliveryZone $zone): float => (float) $zone->costo_base)
            ->values();

        if ($zones->isEmpty()) {
            return null;
        }

        $latitude = $this->coordinate($direccion->latitude);
        $longitude = $this->coordinate($direccion->longitude);
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $zonesWithPolygons = $zones->filter(fn (DeliveryZone $zone): bool => $this->hasUsablePolygon($zone));

        if ($zonesWithPolygons->isNotEmpty()) {
            $polygonZone = $zonesWithPolygons->first(
                fn (DeliveryZone $zone): bool => $this->pointInsideZone($latitude, $longitude, $zone)
            );

            if ($polygonZone !== null) {
                return $polygonZone;
            }
        }

        return $this->nearestZoneByCenter($zones, $latitude, $longitude)
            ?? $this->zoneByAddressText($zones, $direccion);
    }

    /**
     * Devuelve el costo de envio para una direccion cubierta.
     */
    public function deliveryCostFor(Direccion $direccion): ?float
    {
        $zone = $this->findActiveZoneFor($direccion);

        return $zone === null ? null : (float) $zone->costo_base;
    }

    /**
     * Devuelve el costo de envio de un vendedor para una direccion cubierta.
     */
    public function deliveryCostForVendor(Direccion $direccion, ?int $vendorId): ?float
    {
        $zone = $this->findActiveZoneFor($direccion);

        if ($zone === null) {
            return null;
        }

        if ($vendorId === null || $vendorId <= 0) {
            return (float) $zone->costo_base;
        }

        $vendorZone = VendorDeliveryZone::query()
            ->where('vendor_id', $vendorId)
            ->where('delivery_zone_id', $zone->id)
            ->where('activa', true)
            ->first();

        return $vendorZone?->costo_override !== null
            ? (float) $vendorZone->costo_override
            : (float) $zone->costo_base;
    }

    /**
     * Suma el envio por vendedor para carritos multivendedor.
     *
     * @param iterable<int, int|null> $vendorIds
     */
    public function deliveryCostForVendors(Direccion $direccion, iterable $vendorIds): ?float
    {
        $zone = $this->findActiveZoneFor($direccion);

        if ($zone === null) {
            return null;
        }

        $ids = collect($vendorIds)
            ->map(fn (mixed $vendorId): ?int => $vendorId === null ? null : (int) $vendorId)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return (float) $zone->costo_base;
        }

        return (float) $ids->sum(fn (?int $vendorId): float => $this->deliveryCostForVendor($direccion, $vendorId) ?? 0.0);
    }

    /**
     * Devuelve un estado listo para UI.
     *
     * @return array{covered: bool, zone: DeliveryZone|null, message: string}
     */
    public function coverageStateFor(Direccion $direccion): array
    {
        $zone = $this->findActiveZoneFor($direccion);

        if ($zone !== null) {
            return [
                'covered' => true,
                'zone' => $zone,
                'message' => "Disponible en {$zone->nombre}.",
            ];
        }

        if ($this->hasRealtimeLocation($direccion)) {
            return [
                'covered' => true,
                'zone' => null,
                'message' => 'Ubicacion exacta capturada. El pedido puede continuar.',
            ];
        }

        $supported = implode(' y ', self::SUPPORTED_MUNICIPIOS);

        return [
            'covered' => false,
            'zone' => null,
            'message' => ! $this->hasCoordinates($direccion)
                ? 'Actualiza esta direccion con tu ubicacion exacta para validar cobertura.'
                : ($this->isSupportedMunicipio($direccion->municipio)
                ? 'Aun no hay una zona activa para esta direccion. Puedes elegir otra direccion o pedir soporte.'
                : "Por ahora Atlantia entrega en {$supported}."),
        ];
    }

    /**
     * Indica si el municipio pertenece a la fase operativa actual.
     */
    public function isSupportedMunicipio(?string $municipio): bool
    {
        $canonical = $this->canonicalMunicipio($municipio);

        return $canonical !== null && array_key_exists($canonical, self::SUPPORTED_MUNICIPIOS);
    }

    /**
     * Permite checkout cuando el cliente compartio su ubicacion GPS real.
     */
    public function hasRealtimeLocation(Direccion $direccion): bool
    {
        return $this->hasCoordinates($direccion);
    }

    /**
     * Normaliza municipios escritos con acentos, variantes o textos largos.
     */
    public function canonicalMunicipio(?string $municipio): ?string
    {
        $normalized = $this->normalize($municipio);

        return match (true) {
            $normalized === '' => null,
            str_contains($normalized, 'puerto barrios') => 'puerto-barrios',
            str_contains($normalized, 'santo tomas') => 'santo-tomas',
            str_contains($normalized, 'morales') => 'morales',
            str_contains($normalized, 'los amates') => 'los-amates',
            str_contains($normalized, 'livingston') => 'livingston',
            str_contains($normalized, 'el estor') => 'el-estor',
            default => Str::slug($normalized),
        };
    }

    /**
     * @return Collection<int, DeliveryZone>
     */
    private function activeZones(): Collection
    {
        return $this->zonaEntregaService->activeCached();
    }

    /**
     * @param Collection<int, DeliveryZone> $zones
     */
    private function nearestZoneByCenter(Collection $zones, float $latitude, float $longitude): ?DeliveryZone
    {
        return $zones
            ->map(function (DeliveryZone $zone) use ($latitude, $longitude): array {
                $centerLatitude = $this->coordinate($zone->latitude_centro);
                $centerLongitude = $this->coordinate($zone->longitude_centro);

                if ($centerLatitude === null || $centerLongitude === null) {
                    return ['zone' => $zone, 'distance' => null, 'radius' => self::DEFAULT_ZONE_RADIUS_KM];
                }

                return [
                    'zone' => $zone,
                    'distance' => $this->distanceKm($latitude, $longitude, $centerLatitude, $centerLongitude),
                    'radius' => $this->zoneRadiusKm($zone),
                ];
            })
            ->filter(fn (array $candidate): bool => $candidate['distance'] !== null && $candidate['distance'] <= $candidate['radius'])
            ->sortBy('distance')
            ->first()['zone'] ?? null;
    }

    private function hasUsablePolygon(DeliveryZone $zone): bool
    {
        return count($this->polygonRings($zone)) > 0;
    }

    private function pointInsideZone(float $latitude, float $longitude, DeliveryZone $zone): bool
    {
        foreach ($this->polygonRings($zone) as $ring) {
            if ($this->pointInRing($latitude, $longitude, $ring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<int, array{0: mixed, 1: mixed}>>
     */
    private function polygonRings(DeliveryZone $zone): array
    {
        $features = $zone->poligono_geojson['features'] ?? [];
        $rings = [];

        foreach ($features as $feature) {
            $geometry = $feature['geometry'] ?? [];
            $type = $geometry['type'] ?? null;
            $coordinates = $geometry['coordinates'] ?? [];

            if ($type === 'Polygon') {
                $rings[] = $coordinates[0] ?? [];
            }

            if ($type === 'MultiPolygon') {
                foreach ($coordinates as $polygon) {
                    $rings[] = $polygon[0] ?? [];
                }
            }
        }

        return array_values(array_filter($rings, fn (array $ring): bool => count($ring) >= 3));
    }

    /**
     * @param array<int, array{0: mixed, 1: mixed}> $ring
     */
    private function pointInRing(float $latitude, float $longitude, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) ($ring[$i][0] ?? 0);
            $yi = (float) ($ring[$i][1] ?? 0);
            $xj = (float) ($ring[$j][0] ?? 0);
            $yj = (float) ($ring[$j][1] ?? 0);

            $intersects = (($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < ($xj - $xi) * ($latitude - $yi) / (($yj - $yi) ?: 0.0000001) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Cuando una zona se administra sin mapa, valida por colonia/barrio/codigo
     * manteniendo la ubicacion GPS exacta de la direccion para reparto.
     *
     * @param Collection<int, DeliveryZone> $zones
     */
    private function zoneByAddressText(Collection $zones, Direccion $direccion): ?DeliveryZone
    {
        $addressText = $this->normalize(implode(' ', array_filter([
            $direccion->zona_o_barrio,
            $direccion->direccion_linea_1,
            $direccion->direccion_linea_2,
            $direccion->referencia,
            $direccion->alias,
        ])));

        if ($addressText === '') {
            return null;
        }

        return $zones->first(function (DeliveryZone $zone) use ($addressText): bool {
            foreach ($this->zoneSearchTerms($zone) as $term) {
                if ($term !== '' && (str_contains($addressText, $term) || str_contains($term, $addressText))) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @return array<int, string>
     */
    private function zoneSearchTerms(DeliveryZone $zone): array
    {
        $barrios = $zone->poligono_geojson['metadata']['barrios'] ?? [];
        $terms = [
            $zone->nombre,
            $zone->slug,
            $zone->descripcion,
            ...$barrios,
        ];

        return collect($terms)
            ->map(fn (mixed $term): string => $this->stripAreaPrefix($this->normalize($term)))
            ->filter(fn (string $term): bool => strlen($term) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    private function stripAreaPrefix(string $value): string
    {
        return (string) Str::of($value)
            ->replaceMatches('/\b(colonia|barrio|zona|residencial|sector|aldea|caserio|lotificacion)\b/', ' ')
            ->squish();
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function hasCoordinates(Direccion $direccion): bool
    {
        return $this->coordinate($direccion->latitude) !== null
            && $this->coordinate($direccion->longitude) !== null;
    }

    private function zoneRadiusKm(DeliveryZone $zone): float
    {
        $radius = $zone->poligono_geojson['metadata']['radio_km'] ?? null;

        return is_numeric($radius) && (float) $radius > 0
            ? (float) $radius
            : self::DEFAULT_ZONE_RADIUS_KM;
    }

    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function normalize(mixed $value): string
    {
        return (string) Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }
}
