<?php

namespace App\DTOs;

use App\Enums\MetodoPago;
use App\Models\Pedido;
use App\ValueObjects\Dinero;

/**
 * DTO de checkout y procesamiento de pedido.
 */
final readonly class PedidoDTO
{
    /**
     * @param int|null $pedidoId
     * @param string|null $uuid
     * @param string|null $numeroPedido
     * @param int|null $clienteId
     * @param int $direccionId
     * @param MetodoPago $metodoPago
     * @param Dinero $envio
     * @param string|null $notas
     * @param string|null $cardToken
     * @param string|null $referenciaBancaria
     * @param string|null $comprobantePath
     * @param string|null $cuponCodigo
     */
    public function __construct(
        public ?int $pedidoId,
        public ?string $uuid,
        public ?string $numeroPedido,
        public ?int $clienteId,
        public int $direccionId,
        public MetodoPago $metodoPago,
        public Dinero $envio,
        public ?string $notas,
        public ?string $cardToken,
        public ?string $referenciaBancaria,
        public ?string $comprobantePath,
        public ?string $cuponCodigo
    ) {
    }

    /**
     * Crea DTO desde payload de checkout validado.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromCheckoutArray(array $data): self
    {
        return new self(
            pedidoId: null,
            uuid: null,
            numeroPedido: null,
            clienteId: null,
            direccionId: (int) $data['direccion_id'],
            metodoPago: MetodoPago::from((string) $data['metodo_pago']),
            envio: Dinero::from($data['envio'] ?? 0),
            notas: isset($data['notas']) ? (string) $data['notas'] : null,
            cardToken: isset($data['card_token']) ? (string) $data['card_token'] : null,
            referenciaBancaria: isset($data['referencia_bancaria']) ? (string) $data['referencia_bancaria'] : null,
            comprobantePath: isset($data['comprobante_path']) ? (string) $data['comprobante_path'] : null,
            cuponCodigo: isset($data['coupon_code']) ? (string) $data['coupon_code'] : null,
        );
    }

    /**
     * Crea DTO minimo desde un pedido persistido.
     *
     * @param Pedido $pedido
     * @return self
     */
    public static function fromModel(Pedido $pedido): self
    {
        return new self(
            pedidoId: $pedido->id,
            uuid: $pedido->uuid,
            numeroPedido: $pedido->numero_pedido,
            clienteId: $pedido->cliente_id,
            direccionId: $pedido->direccion_id,
            metodoPago: $pedido->metodo_pago instanceof MetodoPago ? $pedido->metodo_pago : MetodoPago::from($pedido->metodoPagoValor()),
            envio: Dinero::from($pedido->envio),
            notas: $pedido->notas,
            cardToken: null,
            referenciaBancaria: null,
            comprobantePath: null,
            cuponCodigo: null,
        );
    }

    /**
     * Devuelve payload de pasarela a partir del checkout.
     *
     * @return array<string, mixed>
     */
    public function toPaymentPayload(): array
    {
        return [
            'metodo_pago' => $this->metodoPago,
            'envio' => $this->envio->toDecimal(),
            'notas' => $this->notas,
            'card_token' => $this->cardToken,
            'referencia_bancaria' => $this->referenciaBancaria,
            'comprobante_path' => $this->comprobantePath,
        ];
    }
}
