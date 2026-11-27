<?php

namespace Tests\Feature\Cliente;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Clientes\ReferidosService;
use App\Services\Clientes\SaldoService;
use App\Services\Pedidos\EstadoPedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferidosYSaldoTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_friends_get_credit_when_first_order_is_delivered(): void
    {
        $padrino = User::factory()->create(['name' => 'Carlos Pérez']);
        $codigo = app(ReferidosService::class)->codigoDe($padrino);

        /* El amigo abre el enlace y crea su cuenta: queda vinculado. */
        $this->get(route('referidos.invitacion', $codigo))->assertRedirect(route('register'));
        $amigo = User::factory()->create();
        $this->assertSame($padrino->id, $amigo->fresh()->referido_por_id);

        $pedido = Pedido::factory()->create(['cliente_id' => $amigo->id, 'estado' => 'en_ruta']);
        app(EstadoPedidoService::class)->registrar($pedido, EstadoPedido::Entregado);
        app(EstadoPedidoService::class)->registrar($pedido, EstadoPedido::Entregado);

        $saldo = app(SaldoService::class);
        $this->assertEqualsWithDelta(15.0, $saldo->disponible($amigo), 0.001);
        $this->assertEqualsWithDelta(15.0, $saldo->disponible($padrino), 0.001);
    }

    public function test_cannot_refer_yourself(): void
    {
        $user = User::factory()->create();
        app(ReferidosService::class)->vincular($user, app(ReferidosService::class)->codigoDe($user));

        $this->assertNull($user->fresh()->referido_por_id);
    }

    public function test_customer_sees_balance_and_invite_link(): void
    {
        Role::findOrCreate('cliente', 'web');
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');
        app(SaldoService::class)->acreditar($cliente, 12.5, 'ajuste_pedido', 'Faltó un producto');

        $this->actingAs($cliente)
            ->get(route('cliente.saldo.index'))
            ->assertOk()
            ->assertSee('Q 12.50')
            ->assertSee('Invitar por WhatsApp')
            ->assertSee('Faltó un producto');
    }
}
