<?php

namespace App\Contracts;

use App\DTOs\PagoResultado;
use App\DTOs\PedidoDTO;
use App\Models\Payment;
use App\Models\Pedido;

/**
 * Contrato de integracion con pasarela de pago.
 */
interface PasarelaPagoContract
{
    /**
     * Procesa un intento de pago y devuelve el resultado normalizado.
     *
     * @param  array<string, mixed>  $datos
     */
    public function procesar(array $datos): PagoResultado;

    /**
     * Registra un pago de checkout dentro del flujo de pedidos.
     */
    public function registrarPagoCheckout(Pedido $pedido, PedidoDTO $pedidoDTO): Payment;

    /**
     * Procesa un reembolso sobre un pago existente.
     */
    public function reembolsar(Payment $payment, float $monto): Payment;
}
