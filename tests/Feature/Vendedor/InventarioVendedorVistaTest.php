<?php

namespace Tests\Feature\Vendedor;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioVendedorVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_shows_real_summary_and_only_real_movements(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $vendor->user->assignRole('vendedor');
        $crema = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Crema Nivea']);
        Inventario::factory()->create(['producto_id' => $crema->id, 'stock_actual' => 40, 'stock_reservado' => 0, 'stock_minimo' => 5]);
        $agua = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Agua micelar']);
        Inventario::factory()->create(['producto_id' => $agua->id, 'stock_actual' => 2, 'stock_reservado' => 0, 'stock_minimo' => 5]);

        $this->actingAs($vendor->user)->get(route('vendedor.inventario.index'))
            ->assertOk()
            ->assertSee('42 unidades')
            ->assertSee('Todavía no hay movimientos.')
            ->assertDontSee('Entrada de stock')
            ->assertDontSee('Venta realizada')
            ->assertDontSee('Atlantia Supermarket');

        $this->actingAs($vendor->user)->put(route('vendedor.inventario.update', $crema->uuid), [
            'stock_actual' => 46, 'stock_minimo' => 5, 'stock_maximo' => 100,
        ])->assertSessionHasNoErrors();

        $this->actingAs($vendor->user)->get(route('vendedor.inventario.index'))
            ->assertSee('Agregaste existencias')
            ->assertSee('+6');
    }
}
