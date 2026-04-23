<?php

namespace App\Contracts;

use App\DTOs\PagoResultado;
use App\Models\Payment;
use App\Models\Pedido;
use App\DTOs\PedidoDTO;

/**
 * Contrato de integracion con pasarela de pago.
 */
interface PasarelaPagoContract
{
    /**
     * Procesa un intento de pago y devuelve el resultado normalizado.
     *
     * @param array<string, mixed> $datos
     * @return PagoResultado
     */
    public function procesar(array $datos): PagoResultado;

    /**
     * Registra un pago de checkout dentro del flujo de pedidos.
     *
     * @param Pedido $pedido
     * @param PedidoDTO $pedidoDTO
     * @return Payment
     */
    public function registrarPagoCheckout(Pedido $pedido, PedidoDTO $pedidoDTO): Payment;

    /**
     * Procesa un reembolso sobre un pago existente.
     *
     * @param Payment $payment
     * @param float $monto
     * @return Payment
     */
    public function reembolsar(Payment $payment, float $monto): Payment;
}
