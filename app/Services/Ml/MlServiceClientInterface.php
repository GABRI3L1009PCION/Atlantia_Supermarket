<?php

namespace App\Services\Ml;

/**
 * Contrato del cliente HTTP hacia el microservicio ML.
 */
interface MlServiceClientInterface
{
    /**
     * Ejecuta una solicitud al microservicio ML.
     *
     * @param string $endpoint
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array;

    /**
     * Consulta un endpoint del microservicio ML.
     *
     * @param string $endpoint
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array;
}
