<?php

namespace App\DTOs;

/**
 * DTO de item de carrito entre capas.
 */
final readonly class CarritoItemDTO
{
    public function __construct(
        public int $productoId,
        public int $cantidad
    ) {}

    /**
     * Crea DTO desde datos validados.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productoId: (int) ($data['producto_id'] ?? 0),
            cantidad: (int) ($data['cantidad'] ?? 1),
        );
    }
}
