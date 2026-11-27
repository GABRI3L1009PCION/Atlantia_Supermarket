<?php

namespace Tests\Feature\Vendedor;

use App\Models\Devolucion;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\DevolucionVendedorNotification;
use App\Services\Pedidos\DevolucionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionesVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_sees_return_responds_and_refund_voids_its_payout(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $tienda = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $tienda->user->assignRole('vendedor');
        $otra = Vendor::factory()->approved()->create();
        $otra->user->assignRole('vendedor');
        $cliente = User::factory()->create(['name' => 'Ana Gómez']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $padre = Pedido::factory()->create(['vendor_id' => null, 'cliente_id' => $cliente->id, 'estado' => 'entregado', 'total' => 100, 'estado_pago' => 'pagado']);
        $hijo = Pedido::factory()->create(['vendor_id' => $tienda->id, 'cliente_id' => $cliente->id, 'pedido_padre_id' => $padre->id, 'estado' => 'entregado', 'total' => 100, 'envio' => 0, 'propina' => 0]);
        $payment = Payment::query()->create(['uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'metodo' => 'tarjeta', 'monto' => 100, 'estado' => 'aprobado']);
        $split = PaymentSplit::query()->create(['payment_id' => $payment->id, 'vendor_id' => $tienda->id, 'pedido_id' => $hijo->id, 'monto_bruto' => 100, 'comision_atlantia' => 0, 'comision_pasarela' => 0, 'monto_neto_vendedor' => 100, 'estado' => 'pendiente']);

        $devolucion = app(DevolucionService::class)->solicitar($padre, $cliente, ['motivo' => 'producto_defectuoso', 'descripcion' => 'Llegó roto el frasco.']);

        $this->assertSame(1, $tienda->user->notifications()->where("type", DevolucionVendedorNotification::class)->count());
        $this->assertSame(0, $otra->user->notifications()->where("type", DevolucionVendedorNotification::class)->count());

        $this->actingAs($tienda->user)->get(route('vendedor.devoluciones.index'))
            ->assertOk()
            ->assertSee('Pedido #'.$hijo->numero_pedido)
            ->assertSee('Llegó roto el frasco.')
            ->assertSee('Ana G.')
            ->assertSee('¿Qué opinas de esta devolución?');
        $this->actingAs($otra->user)->get(route('vendedor.devoluciones.index'))->assertDontSee('Llegó roto el frasco.');
        $this->actingAs($otra->user)->post(route('vendedor.devoluciones.responder', $devolucion), ['de_acuerdo' => '1'])->assertForbidden();

        $this->actingAs($tienda->user)->post(route('vendedor.devoluciones.responder', $devolucion), ['de_acuerdo' => '0'])
            ->assertSessionHasErrors('comentario');
        $this->actingAs($tienda->user)->post(route('vendedor.devoluciones.responder', $devolucion), ['de_acuerdo' => '0', 'comentario' => 'Salió sellado.'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($devolucion->refresh()->vendedor_de_acuerdo);

        $this->actingAs($admin)->get(route('admin.devoluciones.index'))->assertOk()->assertSee('La tienda no está de acuerdo.');

        app(DevolucionService::class)->resolver($devolucion, $admin, ['decision' => 'aprobada', 'monto_reembolso' => 100]);

        $this->assertSame('cancelado', $hijo->refresh()->estadoValor());
        $this->assertSame('reversado', $split->refresh()->estado);
        $this->assertSame(2, $tienda->user->notifications()->where("type", DevolucionVendedorNotification::class)->count());
        $this->actingAs($tienda->user->fresh())->get(route('vendedor.devoluciones.index'))
            ->assertSee('Esta venta ya no se te paga.');
    }
}
