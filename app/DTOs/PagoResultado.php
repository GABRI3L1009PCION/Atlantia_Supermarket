<?php

namespace App\DTOs;

use App\Enums\EstadoPago;

/**
 * DTO normalizado del resultado de pago.
 */
final readonly class PagoResultado
{
    /**
     * @param EstadoPago $estado
     * @param string|null $transaccionIdPasarela
     * @param bool $hmacValidado
     * @param string|null $referenciaBancaria
     * @param \Illuminate\Support\Carbon|null $validadoAt
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public EstadoPago $estado,
        public ?string $transaccionIdPasarela = null,
        public bool $hmacValidado = false,
        public ?string $referenciaBancaria = null,
        public ?\Illuminate\Support\Carbon $validadoAt = null,
        public array $payload = []
    ) {
    }

    /**
     * Devuelve estructura para persistencia.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado->value,
            'transaccion_id_pasarela' => $this->transaccionIdPasarela,
            'hmac_validado' => $this->hmacValidado,
            'referencia_bancaria' => $this->referenciaBancaria,
            'validado_at' => $this->validadoAt,
            ...$this->payload,
        ];
    }
}
