<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\GastoFijo;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Support\MargenProducto;
use App\Support\PedidoMinimo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentabilidadTest extends TestCase
{
    use RefreshDatabase;

    private function clienteConCarrito(Vendor $vendor, float $precio, int $cantidad = 1): User
    {
        Role::findOrCreate('cliente', 'web');
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');
        $producto = Producto::factory()->create(['vendor_id' => $vendor->id, 'precio_base' => $precio]);
        $carrito = Carrito::query()->create(['uuid' => (string) Str::uuid(), 'user_id' => $cliente->id, 'estado' => 'activo']);
        CarritoItem::query()->create(['carrito_id' => $carrito->id, 'producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio_unitario_snapshot' => $precio]);

        return $cliente;
    }

    public function test_store_minimum_blocks_small_orders_until_reached(): void
    {
        $vendor = Vendor::factory()->approved()->create(['pedido_minimo' => 50]);
        $cliente = $this->clienteConCarrito($vendor, 12.50, 2);

        $this->actingAs($cliente)->get(route('cliente.carrito.index'))
            ->assertOk()
            ->assertSee('Agrega Q 25.00 más de esta tienda')
            ->assertSee('Completa el pedido mínimo');

        $this->actingAs($cliente)->get(route('cliente.checkout.create'))
            ->assertRedirect(route('cliente.carrito.index'))
            ->assertSessionHas('error');

        CarritoItem::query()->update(['cantidad' => 4]);
        $this->assertTrue(PedidoMinimo::pendientes(CarritoItem::query()->with('producto.vendor')->get())->isEmpty());
    }

    public function test_vendor_sets_own_minimum_order(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)->put(route('vendedor.operacion.update'), [
            'hora_apertura' => '07:00', 'hora_cierre' => '20:00', 'tiempo_preparacion_min' => 20, 'pedido_minimo' => 75,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(75.0, (float) $vendor->fresh()->pedido_minimo, 0.001);
    }

    public function test_purchase_cost_is_private_and_margin_is_flagged(): void
    {
        $producto = Producto::factory()->create(['precio_base' => 10.00, 'precio_oferta' => null, 'costo_compra' => 9.50]);

        $this->assertArrayNotHasKey('costo_compra', $producto->toArray());
        $margen = MargenProducto::calcular($producto);
        $this->assertEqualsWithDelta(5.0, $margen['porcentaje'], 0.01);
        $this->assertFalse($margen['suficiente']);

        $producto->update(['costo_compra' => 8.00]);
        $this->assertTrue(MargenProducto::calcular($producto->fresh())['suficiente']);
    }

    public function test_profit_report_includes_fixed_costs(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.utilidad.gastos.store'), [
            'mes' => now()->format('Y-m'), 'categoria' => 'tecnologia', 'concepto' => 'Servidor', 'monto' => 450,
        ])->assertRedirect();

        $this->assertSame(1, GastoFijo::query()->count());
        $this->actingAs($admin)->get(route('admin.utilidad.index'))
            ->assertOk()
            ->assertSee('Utilidad del mes')
            ->assertSee('Servidor')
            ->assertSee('− Q 450.00');
    }
}
