<?php

namespace Tests\Unit\Geolocalizacion;

use App\Services\Geolocalizacion\EtaCalculadorService;
use App\Services\Geolocalizacion\RutaOptimaService;
use App\Services\Geolocalizacion\TspOptimizadorService;
use Tests\TestCase;

/**
 * Pruebas del fallback local de rutas.
 */
class RutaOptimaServiceTest extends TestCase
{
    /**
     * Calcula una ruta local determinista en ambiente de pruebas.
     */
    public function testCalculatesLocalRouteWhenMapboxIsDisabled(): void
    {
        config(['services.mapbox.token' => null]);
        $service = new RutaOptimaService(new EtaCalculadorService(), new TspOptimizadorService());

        $route = $service->calcularEntrePuntos(
            ['latitude' => 15.7309, 'longitude' => -88.5944],
            [
                ['latitude' => 15.6969, 'longitude' => -88.6206, 'label' => 'Santo Tomas'],
                ['latitude' => 15.4725, 'longitude' => -88.8409, 'label' => 'Morales'],
            ],
        );

        $this->assertSame('local_haversine', $route['provider']);
        $this->assertGreaterThan(0, $route['distancia_km']);
        $this->assertGreaterThan(0, $route['tiempo_estimado_min']);
        $this->assertSame('LineString', $route['geometry']['type']);
    }
}
