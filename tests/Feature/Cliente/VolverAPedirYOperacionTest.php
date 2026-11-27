<?php

namespace Tests\Feature\Cliente;

use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Support\HorarioTienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VolverAPedirYOperacionTest extends TestCase
{
    use RefreshDatabase;

    private function productoConStock(int $stock): Producto
    {
        $producto = Producto::factory()->publicado()->create();
        Inventario::query()->updateOrCreate(['producto_id' => $producto->id], [
            'stock_actual' => $stock,
            'stock_reservado' => 0,
            'stock_minimo' => 0,
            'stock_maximo' => 100,
            'ultima_actualizacion' => now(),
        ]);

        return $producto->fresh();
    }

    public function test_reorder_puts_available_products_back_in_the_cart(): void
    {
        Role::findOrCreate('cliente', 'web');
        $pedido = Pedido::factory()->create(['estado' => 'entregado']);
        $pedido->cliente->assignRole('cliente');

        $disponible = $this->productoConStock(10);
        $agotado = $this->productoConStock(0);

        foreach ([[$disponible, 2], [$agotado, 1]] as [$producto, $cantidad]) {
            PedidoItem::query()->create([
                'pedido_id' => $pedido->id,
                'producto_id' => $producto->id,
                'producto_nombre_snapshot' => $producto->nombre,
                'producto_sku_snapshot' => $producto->sku,
                'cantidad' => $cantidad,
                'precio_unitario_snapshot' => 10,
                'subtotal' => 10 * $cantidad,
                'descuento' => 0,
                'impuestos' => 0,
            ]);
        }

        $this->actingAs($pedido->cliente)
            ->post(route('cliente.pedidos.repetir', $pedido))
            ->assertRedirect(route('cliente.carrito.index'))
            ->assertSessionHas('success', fn (string $mensaje): bool => str_contains($mensaje, 'Agregamos 1 producto')
                && str_contains($mensaje, $agotado->nombre));

        $this->assertDatabaseHas('carrito_items', ['producto_id' => $disponible->id, 'cantidad' => 2]);
        $this->assertDatabaseMissing('carrito_items', ['producto_id' => $agotado->id]);
    }

    public function test_customer_cannot_reorder_someone_elses_order(): void
    {
        Role::findOrCreate('cliente', 'web');
        $pedido = Pedido::factory()->create(['estado' => 'entregado']);
        $otro = User::factory()->create(['email_verified_at' => now()]);
        $otro->assignRole('cliente');

        $this->actingAs($otro)->post(route('cliente.pedidos.repetir', $pedido))->assertForbidden();
    }

    public function test_store_hours_messages(): void
    {
        $vendor = Vendor::factory()->approved()->create(['hora_apertura' => '07:00', 'hora_cierre' => '21:00']);

        $this->assertTrue(HorarioTienda::estado($vendor, now()->setTime(10, 0))['abierto']);
        $this->assertSame('Cerrado, abrimos a las 7:00 a. m.', HorarioTienda::estado($vendor, now()->setTime(5, 0))['mensaje']);
        $this->assertSame('Cerrado, abrimos mañana a las 7:00 a. m.', HorarioTienda::estado($vendor, now()->setTime(22, 0))['mensaje']);
    }

    public function test_vendor_can_pause_and_resume_the_store(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)
            ->post(route('vendedor.operacion.pausar'), ['duracion' => '60'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(HorarioTienda::estado($vendor->fresh())['pausado']);

        $this->actingAs($vendor->user)->post(route('vendedor.operacion.reanudar'));
        $this->assertFalse(HorarioTienda::estado($vendor->fresh())['pausado']);

        $this->actingAs($vendor->user)
            ->put(route('vendedor.operacion.update'), [
                'hora_apertura' => '08:00',
                'hora_cierre' => '18:00',
                'tiempo_preparacion_min' => 25,
                'alerta_sonora_pedidos' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(25, $vendor->fresh()->tiempo_preparacion_min);

        $this->actingAs($vendor->user)
            ->getJson(route('vendedor.pedidos.nuevos'))
            ->assertOk()
            ->assertJsonPath('alerta_sonora', true);
    }
}
