<?php

namespace App\Services\Geolocalizacion;

/**
 * Optimizador simple de paradas usando vecino mas cercano.
 */
class TspOptimizadorService
{
    /**
     * Ordena paradas para minimizar recorrido aproximado.
     *
     * @param array<string, float> $origen
     * @param array<int, array<string, mixed>> $paradas
     * @return array<int, array<string, mixed>>
     */
    public function ordenarParadas(array $origen, array $paradas): array
    {
        $actual = $origen;
        $pendientes = array_values($paradas);
        $ordenadas = [];

        while ($pendientes !== []) {
            $indiceCercano = $this->indiceCercano($actual, $pendientes);
            $siguiente = $pendientes[$indiceCercano];
            $ordenadas[] = $siguiente;
            $actual = $siguiente;
            array_splice($pendientes, $indiceCercano, 1);
        }

        return $ordenadas;
    }

    /**
     * Encuentra la parada mas cercana al punto actual.
     *
     * @param array<string, float> $actual
     * @param array<int, array<string, mixed>> $paradas
     * @return int
     */
    private function indiceCercano(array $actual, array $paradas): int
    {
        $mejorIndice = 0;
        $mejorDistancia = PHP_FLOAT_MAX;

        foreach ($paradas as $indice => $parada) {
            $distancia = (($actual['latitude'] - $parada['latitude']) ** 2)
                + (($actual['longitude'] - $parada['longitude']) ** 2);

            if ($distancia < $mejorDistancia) {
                $mejorDistancia = $distancia;
                $mejorIndice = $indice;
            }
        }

        return $mejorIndice;
    }
}
