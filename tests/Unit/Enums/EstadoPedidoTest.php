<?php

namespace Tests\Unit\Enums;

use App\Enums\EstadoPedido;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de transiciones del enum EstadoPedido.
 */
class EstadoPedidoTest extends TestCase
{
    /**
     * Permite transiciones validas del flujo operativo.
     */
    public function testPermiteTransicionesValidas(): void
    {
        $this->assertTrue(EstadoPedido::Pendiente->puedeTransicionarA(EstadoPedido::Confirmado));
        $this->assertTrue(EstadoPedido::Confirmado->puedeTransicionarA(EstadoPedido::EnPreparacion));
        $this->assertTrue(EstadoPedido::EnPreparacion->puedeTransicionarA(EstadoPedido::ListoParaEntrega));
        $this->assertTrue(EstadoPedido::EnRuta->puedeTransicionarA(EstadoPedido::Entregado));
    }

    /**
     * Impide saltos de estado que rompen el flujo.
     */
    public function testNoPermiteSaltarEstados(): void
    {
        $this->assertFalse(EstadoPedido::Pendiente->puedeTransicionarA(EstadoPedido::Entregado));
        $this->assertFalse(EstadoPedido::Confirmado->puedeTransicionarA(EstadoPedido::Entregado));
        $this->assertFalse(EstadoPedido::Cancelado->puedeTransicionarA(EstadoPedido::Confirmado));
        $this->assertFalse(EstadoPedido::Entregado->puedeTransicionarA(EstadoPedido::Pendiente));
    }
}
