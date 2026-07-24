<?php

namespace App\DTOs;

use App\Enums\EstadoPago;
use Illuminate\Support\Carbon;

/**
 * DTO normalizado del resultado de pago.
 */
final readonly class PagoResultado
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public EstadoPago $estado,
        public ?string $transaccionIdPasarela = null,
        public bool $hmacValidado = false,
        public ?string $referenciaBancaria = null,
        public ?Carbon $validadoAt = null,
        public array $payload = []
    ) {}

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
