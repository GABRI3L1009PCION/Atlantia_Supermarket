<?php

namespace Tests\Feature\Checkout;

use App\Jobs\AvisarPedidosRecurrentes;
use App\Jobs\RecordarCarritosAbandonados;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\PedidoRecurrente;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Notifications\CarritoAbandonadoNotification;
use App\Notifications\PedidoRecurrenteNotification;
use App\Services\Promociones\PromocionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PromocionesTest extends TestCase
{
    use RefreshDatabase;

    private function item(Producto $producto, int $cantidad): object
    {
        return (object) ['producto' => $producto, 'cantidad' => $cantidad];
    }

    private function promocion(array $datos): Promocion
    {
        return Promocion::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Promo',
            'activa' => true,
            ...$datos,
        ]);
    }

    public function test_two_for_one_and_three_for_two(): void
    {
        $jugo = Producto::factory()->publicado()->create(['precio_base' => 10, 'precio_oferta' => null]);
        $galleta = Producto::factory()->publicado()->create(['precio_base' => 6, 'precio_oferta' => null]);
        $this->promocion(['tipo' => 'nxm', 'lleva' => 2, 'paga' => 1, 'producto_id' => $jugo->id]);
        $this->promocion(['tipo' => 'nxm', 'lleva' => 3, 'paga' => 2, 'producto_id' => $galleta->id]);

        $resultado = app(PromocionService::class)->descuentoCarrito([
            $this->item($jugo, 5),     // 2 gratis = Q 20
            $this->item($galleta, 3),  // 1 gratis = Q 6
        ]);

        $this->assertEqualsWithDelta(26.0, $resultado['total'], 0.001);
        $this->assertSame(['2x1', '3x2'], array_column($resultado['detalle'], 'etiqueta'));
    }

    public function test_flash_sale_only_applies_during_its_hours(): void
    {
        $producto = Producto::factory()->publicado()->create(['precio_base' => 100, 'precio_oferta' => null]);
        $this->promocion(['tipo' => 'porcentaje', 'porcentaje' => 20, 'producto_id' => $producto->id, 'hora_inicio' => '10:00', 'hora_fin' => '12:00']);

        $this->travelTo(now()->setTime(11, 0));
        $this->assertEqualsWithDelta(20.0, (new PromocionService)->descuentoCarrito([$this->item($producto, 1)])['total'], 0.001);

        $this->travelTo(now()->setTime(13, 0));
        $this->assertEqualsWithDelta(0.0, (new PromocionService)->descuentoCarrito([$this->item($producto, 1)])['total'], 0.001);
    }

    public function test_vendor_promotion_does_not_apply_to_other_stores(): void
    {
        $producto = Producto::factory()->publicado()->create(['precio_base' => 10]);
        $otro = Producto::factory()->publicado()->create(['precio_base' => 10]);
        $this->promocion(['tipo' => 'porcentaje', 'porcentaje' => 50, 'vendor_id' => $producto->vendor_id]);

        $this->assertNotNull((new PromocionService)->promocionPara($producto));
        $this->assertNull((new PromocionService)->promocionPara($otro));
    }

    public function test_first_purchase_discount_is_off_unless_owner_enables_it(): void
    {
        $nuevo = User::factory()->create();

        $this->assertSame(0.0, (float) config('atlantia.primera_compra.porcentaje'));
        $this->assertEqualsWithDelta(0.0, (new PromocionService)->descuentoPrimeraCompra($nuevo, 150), 0.001);
    }

    public function test_first_purchase_discount_only_for_new_registered_customers(): void
    {
        config(['atlantia.primera_compra.porcentaje' => 10]);
        $servicio = new PromocionService;
        $nuevo = User::factory()->create();
        $invitado = User::factory()->create(['email' => 'guest-1@invitados.atlantia.local']);

        $this->assertEqualsWithDelta(15.0, $servicio->descuentoPrimeraCompra($nuevo, 150), 0.001);
        $this->assertEqualsWithDelta(25.0, $servicio->descuentoPrimeraCompra($nuevo, 900), 0.001); // tope
        $this->assertEqualsWithDelta(0.0, $servicio->descuentoPrimeraCompra($nuevo, 80), 0.001);   // minimo
        $this->assertEqualsWithDelta(0.0, $servicio->descuentoPrimeraCompra($invitado, 150), 0.001);

        Pedido::factory()->create(['cliente_id' => $nuevo->id, 'estado' => 'entregado']);
        $this->assertEqualsWithDelta(0.0, $servicio->descuentoPrimeraCompra($nuevo, 150), 0.001);
    }

    public function test_vendor_creates_a_promotion(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $producto = Producto::factory()->publicado()->create();
        $vendor = $producto->vendor;
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)
            ->post(route('vendedor.promociones.store'), ['nombre' => '2x1 en jugos', 'tipo' => '2x1', 'producto_id' => $producto->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promociones', ['vendor_id' => $vendor->id, 'lleva' => 2, 'paga' => 1, 'producto_id' => $producto->id]);
        $this->actingAs($vendor->user)->get(route('vendedor.promociones.index'))->assertOk()->assertSee('2x1 en jugos');
    }

    public function test_abandoned_cart_reminder_is_sent_once(): void
    {
        Notification::fake();
        $cliente = User::factory()->create();
        $producto = Producto::factory()->publicado()->create();
        $carrito = Carrito::query()->create(['uuid' => (string) Str::uuid(), 'user_id' => $cliente->id, 'estado' => 'activo']);
        CarritoItem::query()->create(['carrito_id' => $carrito->id, 'producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario_snapshot' => 10]);
        $carrito->forceFill(['updated_at' => now()->subHours(5)])->saveQuietly();

        (new RecordarCarritosAbandonados)->handle();
        (new RecordarCarritosAbandonados)->handle();

        Notification::assertSentToTimes($cliente, CarritoAbandonadoNotification::class, 1);
    }

    public function test_weekly_recurring_order_notifies_on_its_day(): void
    {
        Notification::fake();
        $this->travelTo(now()->next('Saturday')->setTime(9, 0));
        $pedido = Pedido::factory()->create(['estado' => 'entregado']);
        PedidoRecurrente::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $pedido->cliente_id,
            'pedido_origen_id' => $pedido->id,
            'dia_semana' => 6,
            'activo' => true,
        ]);

        (new AvisarPedidosRecurrentes)->handle();
        (new AvisarPedidosRecurrentes)->handle();

        Notification::assertSentToTimes($pedido->cliente, PedidoRecurrenteNotification::class, 1);
    }

    public function test_recurring_link_loads_the_cart(): void
    {
        Role::findOrCreate('cliente', 'web');
        $pedido = Pedido::factory()->create(['estado' => 'entregado']);
        $pedido->cliente->assignRole('cliente');
        $producto = Producto::factory()->publicado()->create();
        Inventario::query()->updateOrCreate(['producto_id' => $producto->id], ['stock_actual' => 10, 'stock_reservado' => 0, 'stock_minimo' => 0, 'stock_maximo' => 50, 'ultima_actualizacion' => now()]);
        $pedido->items()->create([
            'producto_id' => $producto->id, 'producto_nombre_snapshot' => $producto->nombre, 'producto_sku_snapshot' => $producto->sku,
            'cantidad' => 3, 'precio_unitario_snapshot' => 10, 'subtotal' => 30, 'descuento' => 0, 'impuestos' => 0,
        ]);

        $this->actingAs($pedido->cliente)
            ->post(route('cliente.pedidos.recurrente', $pedido), ['dia_semana' => 6])
            ->assertSessionHas('success');
        $recurrente = PedidoRecurrente::query()->firstOrFail();

        $this->actingAs($pedido->cliente)
            ->get(route('cliente.recurrentes.cargar', $recurrente))
            ->assertRedirect(route('cliente.carrito.index'));

        $this->assertDatabaseHas('carrito_items', ['producto_id' => $producto->id, 'cantidad' => 3]);
    }
}
