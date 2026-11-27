<?php

namespace Tests\Feature\Vendedor;

use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrediccionDemandaVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_opens_from_menu_without_period_and_with_odd_period(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)->get(route('vendedor.predicciones.index'))
            ->assertOk()
            ->assertSee('Próximos 7 días');
        $this->actingAs($vendor->user)->get(route('vendedor.predicciones.index', ['horizonte' => 999]))
            ->assertOk();
    }

    public function test_projection_uses_real_sales_and_never_invents_demand(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $vendido = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Crema que se vende']);
        Inventario::factory()->create(['producto_id' => $vendido->id, 'stock_actual' => 5, 'stock_reservado' => 0]);
        $nuevo = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Producto nuevo']);
        Inventario::factory()->create(['producto_id' => $nuevo->id, 'stock_actual' => 20, 'stock_reservado' => 0]);

        /* 30 unidades vendidas en 30 días = 1 por día → 7 en una semana; solo tiene 5. */
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'created_at' => now()->subDays(3)]);
        PedidoItem::query()->create(['pedido_id' => $pedido->id, 'producto_id' => $vendido->id, 'producto_nombre_snapshot' => 'Crema que se vende', 'cantidad' => 30, 'precio_unitario_snapshot' => 10, 'subtotal' => 300, 'descuento' => 0, 'impuestos' => 0]);
        /* Las ventas canceladas no cuentan. */
        $cancelado = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'cancelado', 'created_at' => now()->subDays(2)]);
        PedidoItem::query()->create(['pedido_id' => $cancelado->id, 'producto_id' => $nuevo->id, 'producto_nombre_snapshot' => 'Producto nuevo', 'cantidad' => 50, 'precio_unitario_snapshot' => 10, 'subtotal' => 500, 'descuento' => 0, 'impuestos' => 0]);

        $this->actingAs($vendor->user)->get(route('vendedor.predicciones.index', ['horizonte' => 7]))
            ->assertOk()
            ->assertSee('Pide más pronto')
            ->assertSee('Te faltarían 2')
            ->assertSee('5 días')
            ->assertSee('Sin ventas aún')
            ->assertSeeInOrder(['Crema que se vende', 'Producto nuevo'])
            ->assertDontSee('flujo ML')
            ->assertDontSee('Atlantia Supermarket');
    }
}
