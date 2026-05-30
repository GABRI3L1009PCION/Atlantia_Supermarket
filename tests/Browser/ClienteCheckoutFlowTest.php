<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas E2E HTTP del flujo principal del cliente.
 */
class ClienteCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El flujo principal permite checkout publico y conserva pedidos autenticados.
     */
    public function testClienteFlowRoutesAreRegisteredWithExpectedMiddleware(): void
    {
        $checkout = Route::getRoutes()->getByName('cliente.checkout.store');
        $pedido = Route::getRoutes()->getByName('cliente.pedidos.show');

        $this->assertNotNull($checkout);
        $this->assertNotNull($pedido);
        $this->assertNotContains('auth', $checkout->gatherMiddleware());
        $this->assertNotContains('role:cliente', $checkout->gatherMiddleware());
        $this->assertSame('cliente/pedidos/{pedido}', $pedido->uri());
    }

    /**
     * Un visitante no autenticado puede entrar al checkout como invitado.
     */
    public function testGuestCanReachCheckout(): void
    {
        $this->get(route('cliente.checkout.create'))->assertOk();
    }
}
