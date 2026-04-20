<?php

namespace App\Services\Geolocalizacion;

/**
 * Servicio de calculo de distancia y tiempo estimado de entrega.
 */
class EtaCalculadorService
{
    private const VELOCIDAD_URBANA_KMH = 24.0;

    /**
     * Calcula distancia Haversine en kilometros.
     *
     * @param float $latOrigen
     * @param float $lngOrigen
     * @param float $latDestino
     * @param float $lngDestino
     * @return float
     */
    public function distanciaKm(float $latOrigen, float $lngOrigen, float $latDestino, float $lngDestino): float
    {
        $radioTierra = 6371.0;
        $dLat = deg2rad($latDestino - $latOrigen);
        $dLng = deg2rad($lngDestino - $lngOrigen);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latOrigen)) * cos(deg2rad($latDestino)) * sin($dLng / 2) ** 2;

        return round($radioTierra * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
    }

    /**
     * Calcula ETA en minutos para reparto local.
     *
     * @param float $distanciaKm
     * @param int $paradas
     * @return int
     */
    public function etaMinutos(float $distanciaKm, int $paradas = 1): int
    {
        $minutosRuta = ($distanciaKm / self::VELOCIDAD_URBANA_KMH) * 60;
        $minutosServicio = max(1, $paradas) * 5;

        return (int) ceil($minutosRuta + $minutosServicio);
    }

    /**
     * Calcula progreso aproximado hacia destino.
     *
     * @param float $distanciaRestanteKm
     * @param float $distanciaTotalKm
     * @return int
     */
    public function progresoPorcentaje(float $distanciaRestanteKm, float $distanciaTotalKm): int
    {
        if ($distanciaTotalKm <= 0.0) {
            return 0;
        }

        return max(0, min(100, (int) round(100 - (($distanciaRestanteKm / $distanciaTotalKm) * 100))));
    }
}
