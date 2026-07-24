<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
    public function test_cliente_flow_routes_are_registered_with_expected_middleware(): void
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
    public function test_guest_can_reach_checkout(): void
    {
        $this->get(route('cliente.checkout.create'))->assertOk();
    }
}
