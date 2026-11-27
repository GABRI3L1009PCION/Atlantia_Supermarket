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

class ReabastoVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_list_is_based_on_real_sales_and_receiving_adds_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        /* 30 vendidas en 30 días = 1/día → 14 para dos semanas + mínimo 5 = 19; tiene 4 → pedir 15. */
        $crema = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Crema rápida']);
        Inventario::factory()->create(['producto_id' => $crema->id, 'stock_actual' => 4, 'stock_reservado' => 0, 'stock_minimo' => 5]);
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'created_at' => now()->subDays(2)]);
        PedidoItem::query()->create(['pedido_id' => $pedido->id, 'producto_id' => $crema->id, 'producto_nombre_snapshot' => 'Crema rápida', 'cantidad' => 30, 'precio_unitario_snapshot' => 10, 'subtotal' => 300, 'descuento' => 0, 'impuestos' => 0]);

        /* Sin ventas y arriba de su mínimo: no se pide (antes se inventaba demanda). */
        $quieto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Producto quieto']);
        Inventario::factory()->create(['producto_id' => $quieto->id, 'stock_actual' => 20, 'stock_reservado' => 0, 'stock_minimo' => 5]);

        $this->actingAs($vendor->user)->get(route('vendedor.reabasto.index'))
            ->assertOk()
            ->assertSee('Crema rápida')
            ->assertSee('Esta semana')
            ->assertSee('4 días')
            ->assertSee('15 <span', false)
            ->assertDontSee('Producto quieto')
            ->assertDontSee('vs. ayer');

        $this->actingAs($vendor->user)->post(route('vendedor.reabasto.recibir', $crema->uuid), ['cantidad' => 15])
            ->assertSessionHasNoErrors();
        $this->assertSame(19, (int) $crema->inventario()->first()->stock_actual);

        $otro = Vendor::factory()->approved()->create();
        $otro->user->assignRole('vendedor');
        $this->actingAs($otro->user)->post(route('vendedor.reabasto.recibir', $crema->uuid), ['cantidad' => 5])->assertForbidden();
    }
}
