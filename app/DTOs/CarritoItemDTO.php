<?php

namespace App\DTOs;

/**
 * DTO de item de carrito entre capas.
 */
final readonly class CarritoItemDTO
{
    /**
     * @param int $productoId
     * @param int $cantidad
     */
    public function __construct(
        public int $productoId,
        public int $cantidad
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
            productoId: (int) ($data['producto_id'] ?? 0),
            cantidad: (int) ($data['cantidad'] ?? 1),
        );
    }
}
