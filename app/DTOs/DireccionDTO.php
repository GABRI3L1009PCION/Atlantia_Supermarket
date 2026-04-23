<?php

namespace App\DTOs;

/**
 * DTO de direccion de entrega.
 */
final readonly class DireccionDTO
{
    /**
     * @param string $alias
     * @param string $nombreContacto
     * @param string $telefonoContacto
     * @param string $municipio
     * @param string|null $zonaOBarrio
     * @param string $direccionLinea1
     * @param string|null $direccionLinea2
     * @param string|null $referencia
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $mapboxPlaceId
     * @param bool $esPrincipal
     */
    public function __construct(
        public string $alias,
        public string $nombreContacto,
        public string $telefonoContacto,
        public string $municipio,
        public ?string $zonaOBarrio,
        public string $direccionLinea1,
        public ?string $direccionLinea2,
        public ?string $referencia,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $mapboxPlaceId,
        public bool $esPrincipal
    ) {
    }

    /**
     * Crea DTO desde datos validados.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            alias: (string) ($data['alias'] ?? 'Casa'),
            nombreContacto: (string) $data['nombre_contacto'],
            telefonoContacto: (string) $data['telefono_contacto'],
            municipio: (string) $data['municipio'],
            zonaOBarrio: isset($data['zona_o_barrio']) ? (string) $data['zona_o_barrio'] : null,
            direccionLinea1: (string) $data['direccion_linea_1'],
            direccionLinea2: isset($data['direccion_linea_2']) ? (string) $data['direccion_linea_2'] : null,
            referencia: isset($data['referencia']) ? (string) $data['referencia'] : null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            mapboxPlaceId: isset($data['mapbox_place_id']) ? (string) $data['mapbox_place_id'] : null,
            esPrincipal: (bool) ($data['es_principal'] ?? false),
        );
    }

    /**
     * Devuelve payload persistible.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'nombre_contacto' => $this->nombreContacto,
            'telefono_contacto' => $this->telefonoContacto,
            'municipio' => $this->municipio,
            'zona_o_barrio' => $this->zonaOBarrio,
            'direccion_linea_1' => $this->direccionLinea1,
            'direccion_linea_2' => $this->direccionLinea2,
            'referencia' => $this->referencia,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'mapbox_place_id' => $this->mapboxPlaceId,
            'es_principal' => $this->esPrincipal,
        ];
    }
}
