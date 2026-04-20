<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pruebas E2E HTTP del flujo principal del cliente.
 */
class ClienteCheckoutFlowTest extends TestCase
{
    /**
     * El flujo principal conserva rutas publicas por UUID y rutas protegidas.
     */
    public function testClienteFlowRoutesAreRegisteredWithExpectedMiddleware(): void
    {
        $checkout = Route::getRoutes()->getByName('cliente.checkout.store');
        $pedido = Route::getRoutes()->getByName('cliente.pedidos.show');

        $this->assertNotNull($checkout);
        $this->assertNotNull($pedido);
        $this->assertContains('auth', $checkout->gatherMiddleware());
        $this->assertContains('role:cliente', $checkout->gatherMiddleware());
        $this->assertSame('cliente/pedidos/{pedido}', $pedido->uri());
    }

    /**
     * Un visitante no autenticado es enviado al login antes de checkout.
     */
    public function testGuestCannotReachCheckout(): void
    {
        $this->get(route('cliente.checkout.create'))->assertRedirect();
    }
}
