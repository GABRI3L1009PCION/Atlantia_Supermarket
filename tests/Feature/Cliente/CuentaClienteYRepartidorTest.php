<?php

namespace Tests\Feature\Cliente;

use App\Models\CalificacionRepartidor;
use App\Models\Cliente\ClienteDetalle;
use App\Models\Cliente\Direccion;
use App\Models\CourierProfile;
use App\Models\DeliveryRoute;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\PushSuscripcion;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PedidoCanceladoNotification;
use App\Notifications\PedidoCanceladoPorClienteNotification;
use App\Services\Pedidos\CancelacionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CuentaClienteYRepartidorTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->cliente = User::factory()->create(['name' => 'Ana Pérez', 'phone' => '55551234']);
        $this->cliente->assignRole('cliente');
    }

    /**
     * Pedido de una tienda: principal + pedido de la tienda + producto con 2 unidades apartadas.
     *
     * @return array{padre: Pedido, hijo: Pedido, vendor: Vendor, inventario: Inventario, pago: Payment}
     */
    private function pedido(string $estadoTienda = 'pendiente', string $metodo = 'tarjeta', string $estadoPago = 'aprobado'): array
    {
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');
        $producto = Producto::factory()->create(['vendor_id' => $vendor->id]);
        $inventario = Inventario::query()->updateOrCreate(['producto_id' => $producto->id], ['stock_actual' => 10, 'stock_reservado' => 2]);

        $padre = Pedido::factory()->create(['cliente_id' => $this->cliente->id, 'vendor_id' => null, 'estado' => 'pendiente', 'total' => 125, 'envio' => 25, 'metodo_pago' => $metodo]);
        $hijo = Pedido::factory()->create(['cliente_id' => $this->cliente->id, 'vendor_id' => $vendor->id, 'pedido_padre_id' => $padre->id, 'estado' => $estadoTienda, 'total' => 125, 'envio' => 25, 'direccion_id' => $padre->direccion_id]);
        PedidoItem::query()->create([
            'pedido_id' => $hijo->id, 'producto_id' => $producto->id, 'cantidad' => 2,
            'precio_unitario_snapshot' => 50, 'subtotal' => 100, 'producto_nombre_snapshot' => $producto->nombre,
        ]);
        $pago = Payment::query()->create(['uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'metodo' => $metodo, 'monto' => 125, 'estado' => $estadoPago]);
        PaymentSplit::query()->create([
            'payment_id' => $pago->id, 'vendor_id' => $vendor->id, 'pedido_id' => $hijo->id,
            'monto_bruto' => 100, 'comision_atlantia' => 7, 'comision_pasarela' => 6.5, 'monto_neto_vendedor' => 86.5, 'estado' => 'pendiente',
        ]);

        return ['padre' => $padre, 'hijo' => $hijo, 'vendor' => $vendor, 'inventario' => $inventario, 'pago' => $pago];
    }

    public function test_cliente_cancela_pedido_pendiente_y_se_devuelve_todo(): void
    {
        Notification::fake();
        ['padre' => $padre, 'hijo' => $hijo, 'vendor' => $vendor, 'inventario' => $inventario, 'pago' => $pago] = $this->pedido();

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertOk()->assertSee('Cancelar pedido');

        $this->actingAs($this->cliente)->post(route('cliente.pedidos.cancelar', $padre), ['motivo' => 'me_equivoque'])
            ->assertRedirect(route('cliente.pedidos.show', $padre))->assertSessionHas('success');

        $this->assertSame('cancelado', $padre->fresh()->estadoValor());
        $this->assertSame('cancelado', $hijo->fresh()->estadoValor());
        $this->assertSame('reembolsado', $pago->fresh()->estadoValor());
        $this->assertSame('reversado', PaymentSplit::query()->where('pedido_id', $hijo->id)->value('estado'));
        $this->assertSame(0, (int) $inventario->fresh()->stock_reservado, 'Las unidades apartadas vuelven al inventario.');
        Notification::assertSentTo($vendor->user, PedidoCanceladoPorClienteNotification::class);
    }

    public function test_el_cliente_ve_el_plazo_de_devolucion_al_cancelar_con_tarjeta(): void
    {
        ['padre' => $padre] = $this->pedido();

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))
            ->assertSee('Recibimos tu pedido')->assertDontSee('Tu pedido esta en camino')
            ->assertSee('de 30 minutos a 1 hora');

        $this->actingAs($this->cliente)->post(route('cliente.pedidos.cancelar', $padre), ['motivo' => 'ya_no'])
            ->assertSessionHas('success', fn (string $m): bool => str_contains($m, 'Q125.00') && str_contains($m, 'de 30 minutos a 1 hora'));

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertSee('Pedido cancelado')->assertSee('Te devolvemos Q125.00');
    }

    public function test_si_una_tienda_cancela_su_parte_se_devuelve_solo_lo_suyo_y_luego_el_resto(): void
    {
        Notification::fake();
        ['padre' => $padre, 'hijo' => $hijoA, 'vendor' => $tiendaA, 'inventario' => $inventarioA, 'pago' => $pago] = $this->pedido();
        $padre->update(['total' => 230]);
        $pago->update(['monto' => 230]);

        /* Segunda tienda en el mismo pedido: Q80 de productos + Q25 de envío. */
        $tiendaB = Vendor::factory()->approved()->create();
        $tiendaB->user->assignRole('vendedor');
        $productoB = Producto::factory()->create(['vendor_id' => $tiendaB->id]);
        $inventarioB = Inventario::query()->updateOrCreate(['producto_id' => $productoB->id], ['stock_actual' => 5, 'stock_reservado' => 1]);
        $hijoB = Pedido::factory()->create(['cliente_id' => $this->cliente->id, 'vendor_id' => $tiendaB->id, 'pedido_padre_id' => $padre->id, 'estado' => 'pendiente', 'total' => 105, 'envio' => 25, 'direccion_id' => $padre->direccion_id]);
        PedidoItem::query()->create(['pedido_id' => $hijoB->id, 'producto_id' => $productoB->id, 'cantidad' => 1, 'precio_unitario_snapshot' => 80, 'subtotal' => 80, 'producto_nombre_snapshot' => $productoB->nombre]);

        /* La tienda A cancela su parte: se devuelven sus Q100 de productos, el resto del pedido sigue. */
        $this->actingAs($tiendaA->user)->patch(route('vendedor.pedidos.estado', $hijoA), ['estado' => 'cancelado'])
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $inventarioA->fresh()->stock_reservado);
        $this->assertSame(1, (int) $inventarioB->fresh()->stock_reservado);
        $this->assertSame('reversado', PaymentSplit::query()->where('pedido_id', $hijoA->id)->value('estado'));
        $this->assertNotSame('cancelado', $padre->fresh()->estadoValor());
        $this->assertEqualsWithDelta(100.0, (float) $pago->fresh()->pasarela_payload['refund_total'], 0.001);
        $this->assertSame('aprobado', $pago->fresh()->estadoValor(), 'Con devolución parcial el pago sigue acreditado por el resto.');
        Notification::assertSentTo($this->cliente, PedidoCanceladoNotification::class, function ($n) {
            $datos = $n->toDatabase($this->cliente);

            return str_contains($datos['message'], 'Q100.00') && str_contains($datos['message'], 'de 30 minutos a 1 hora') && str_contains($datos['message'], 'Lo demás sigue en camino');
        });

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertSee('no pudo entregar su parte');

        /* Repetir la cancelación no devuelve dos veces. */
        app(CancelacionService::class)->alCancelarse($hijoA->fresh(), $tiendaA->user, 'tienda');
        $this->assertEqualsWithDelta(100.0, (float) $pago->fresh()->pasarela_payload['refund_total'], 0.001);

        /* La tienda B también cancela: ya no queda nada que entregar, se devuelve el resto y se cancela todo. */
        $this->actingAs($tiendaB->user)->patch(route('vendedor.pedidos.estado', $hijoB), ['estado' => 'cancelado'])->assertSessionHas('success');

        $this->assertSame('cancelado', $padre->fresh()->estadoValor());
        $this->assertSame('reembolsado', $pago->fresh()->estadoValor());
        $this->assertEqualsWithDelta(230.0, (float) $pago->fresh()->pasarela_payload['refund_total'], 0.001);
        $this->assertSame(0, (int) $inventarioB->fresh()->stock_reservado);
    }

    public function test_si_atlantia_cancela_el_pedido_completo_se_devuelve_todo_y_se_avisa(): void
    {
        Notification::fake();
        ['padre' => $padre, 'hijo' => $hijo, 'vendor' => $vendor, 'inventario' => $inventario, 'pago' => $pago] = $this->pedido('preparando');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->put(route('admin.pedidos.update', $padre), ['estado' => 'cancelado', 'estado_pago' => 'pagado'])->assertSessionHas('success');

        $this->assertSame('cancelado', $hijo->fresh()->estadoValor());
        $this->assertSame('reembolsado', $pago->fresh()->estadoValor());
        $this->assertSame(0, (int) $inventario->fresh()->stock_reservado);
        Notification::assertSentTo($this->cliente, PedidoCanceladoNotification::class);
        Notification::assertSentTo($vendor->user, PedidoCanceladoPorClienteNotification::class);
    }

    public function test_no_puede_cancelar_si_la_tienda_ya_lo_acepto(): void
    {
        ['padre' => $padre] = $this->pedido('confirmado');

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertDontSee('Sí, cancelar pedido')->assertSee('La tienda ya aceptó tu pedido');
        $this->actingAs($this->cliente)->post(route('cliente.pedidos.cancelar', $padre), ['motivo' => 'ya_no'])->assertSessionHasErrors('pedido');
        $this->assertSame('pendiente', $padre->fresh()->estadoValor());
    }

    public function test_otro_cliente_no_puede_cancelar_un_pedido_ajeno(): void
    {
        ['padre' => $padre] = $this->pedido();
        $otro = User::factory()->create();
        $otro->assignRole('cliente');

        $this->actingAs($otro)->post(route('cliente.pedidos.cancelar', $padre), ['motivo' => 'ya_no'])->assertForbidden();
    }

    public function test_cliente_elimina_su_cuenta_y_se_borran_sus_datos(): void
    {
        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(), 'user_id' => $this->cliente->id, 'alias' => 'Casa', 'nombre_contacto' => 'Ana Pérez',
            'telefono_contacto' => '55551234', 'direccion_linea_1' => '4a calle 12-30', 'municipio' => 'Puerto Barrios', 'zona_o_barrio' => 'Barrio El Centro', 'referencia' => 'Casa azul', 'activa' => true,
        ]);
        PushSuscripcion::query()->create(['user_id' => $this->cliente->id, 'endpoint' => 'https://push.test/1', 'endpoint_hash' => hash('sha256', 'x'), 'p256dh' => 'k', 'auth' => 'a', 'ofertas' => true]);

        $this->actingAs($this->cliente)->get(route('cliente.perfil.edit'))->assertOk()->assertSee('Eliminar mi cuenta');

        $this->actingAs($this->cliente)->delete(route('cliente.perfil.destroy'), ['contrasena' => 'mala', 'confirmo' => '1'])
            ->assertSessionHasErrors('contrasena', null, 'eliminar');
        $this->assertNotSoftDeleted($this->cliente);

        $this->actingAs($this->cliente)->delete(route('cliente.perfil.destroy'), ['contrasena' => 'Atlantia2026!', 'confirmo' => '1'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $usuario = User::withTrashed()->find($this->cliente->id);
        $this->assertSoftDeleted($usuario);
        $this->assertSame('Cliente eliminado', $usuario->name);
        $this->assertStringEndsWith('@eliminados.atlantia.local', $usuario->email);
        $this->assertNull($usuario->phone);
        $this->assertSame('00000000', Direccion::withTrashed()->find($direccion->id)->telefono_contacto);
        $this->assertSame('Dirección eliminada', Direccion::withTrashed()->find($direccion->id)->direccion_linea_1);
        $this->assertSame(0, PushSuscripcion::query()->where('user_id', $usuario->id)->count());
    }

    public function test_no_puede_eliminar_la_cuenta_con_un_pedido_en_curso(): void
    {
        $this->pedido();

        $this->actingAs($this->cliente)->get(route('cliente.perfil.edit'))->assertSee('un pedido en curso');
        $this->actingAs($this->cliente)->delete(route('cliente.perfil.destroy'), ['contrasena' => 'Atlantia2026!', 'confirmo' => '1'])
            ->assertSessionHasErrors('contrasena', null, 'eliminar');
        $this->assertNotSoftDeleted($this->cliente);
    }

    public function test_apaga_los_avisos_de_ofertas_y_se_respeta_en_navegadores_nuevos(): void
    {
        PushSuscripcion::query()->create(['user_id' => $this->cliente->id, 'endpoint' => 'https://push.test/1', 'endpoint_hash' => hash('sha256', 'x'), 'p256dh' => 'k', 'auth' => 'a', 'ofertas' => true]);

        $this->actingAs($this->cliente)->put(route('cliente.perfil.avisos'), ['ofertas' => '0'])->assertSessionHas('success');

        $this->assertFalse((bool) PushSuscripcion::query()->where('user_id', $this->cliente->id)->value('ofertas'));
        $this->assertFalse(ClienteDetalle::query()->where('user_id', $this->cliente->id)->first()->preferencias['ofertas']);
        $this->actingAs($this->cliente)->get(route('cliente.perfil.edit'))->assertOk();
    }

    public function test_cliente_califica_al_repartidor_una_vez_y_cambia_su_promedio(): void
    {
        ['padre' => $padre] = $this->pedido('entregado', 'efectivo', 'aprobado');
        $padre->update(['estado' => 'entregado']);
        $repartidor = User::factory()->create(['name' => 'Carlos Méndez']);
        $repartidor->assignRole('repartidor');
        CourierProfile::query()->create(['user_id' => $repartidor->id, 'rating' => 5]);
        DeliveryRoute::query()->create([
            'uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'repartidor_id' => $repartidor->id,
            'pickup_name' => 'Tienda', 'pickup_latitude' => 15.68, 'pickup_longitude' => -88.61, 'estado' => 'completada',
        ]);

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertSee('¿Cómo te atendió Carlos, tu repartidor?');

        $this->actingAs($this->cliente)->post(route('cliente.pedidos.calificar-repartidor', $padre), ['estrellas' => 3, 'comentario' => 'Llegó tarde pero amable'])
            ->assertSessionHas('success');

        $this->assertEquals(3.0, (float) CourierProfile::query()->where('user_id', $repartidor->id)->value('rating'));
        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $padre))->assertSee('Calificaste al repartidor');

        $this->actingAs($this->cliente)->post(route('cliente.pedidos.calificar-repartidor', $padre), ['estrellas' => 5])->assertSessionHasErrors('estrellas');
        $this->assertSame(1, CalificacionRepartidor::query()->count());

        /* El repartidor ve su calificación y el comentario en su perfil. */
        $this->actingAs($repartidor)->get(route('repartidor.perfil.edit'))->assertOk()->assertSee('3.0')->assertSee('Llegó tarde pero amable');
    }

    public function test_repartidor_actualiza_su_telefono_y_vehiculo(): void
    {
        $repartidor = User::factory()->create();
        $repartidor->assignRole('repartidor');

        $this->actingAs($repartidor)->put(route('repartidor.perfil.update'), ['phone' => '4444 9876', 'vehicle_type' => 'Bicicleta'])->assertSessionHas('success');

        $this->assertSame('4444 9876', $repartidor->fresh()->phone);
        $this->assertSame('Bicicleta', CourierProfile::query()->where('user_id', $repartidor->id)->value('vehicle_type'));

        $this->actingAs($repartidor)->put(route('repartidor.perfil.update'), ['phone' => '12', 'vehicle_type' => 'Avión'])->assertSessionHasErrors(['phone', 'vehicle_type']);
    }
}
