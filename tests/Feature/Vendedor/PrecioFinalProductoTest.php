<?php

namespace Tests\Feature\Vendedor;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Monto;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecioFinalProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_reader_accepts_how_people_type_prices(): void
    {
        $this->assertSame('149', Monto::normalizar('Q 149'));
        $this->assertSame('149.50', Monto::normalizar('Q149,50'));
        $this->assertSame('1250.50', Monto::normalizar('1,250.50'));
        $this->assertSame('1250.50', Monto::normalizar('1.250,50'));
        $this->assertSame('1250', Monto::normalizar('1,250'));
        $this->assertSame('', Monto::normalizar(''));
        $this->assertSame('abc', Monto::normalizar('abc'));
    }

    public function test_vendor_types_final_price_with_q_and_it_is_saved_as_is(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('vendedor');
        Vendor::factory()->approved()->create(['user_id' => $user->id]);
        $categoria = Categoria::query()->create([
            'nombre' => 'Cuidado personal', 'slug' => 'cuidado-personal', 'descripcion' => 'Prueba',
            'icon' => 'bag', 'orden' => 1, 'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('vendedor.productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Mascara de pestanas Sky High',
            'unidad_medida' => 'unidad',
            'precio_sin_iva' => '0',
            'precio_base' => 'Q 149,00',
            'precio_oferta' => 'Q129.50',
            'stock_actual' => 25,
            'stock_minimo' => 5,
            'stock_maximo' => 50,
            'is_active' => '1',
            'visible_catalogo' => '1',
            'requiere_refrigeracion' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $producto = Producto::query()->where('nombre', 'Mascara de pestanas Sky High')->firstOrFail();
        $this->assertEqualsWithDelta(149.00, (float) $producto->precio_base, 0.001);
        $this->assertEqualsWithDelta(129.50, (float) $producto->precio_oferta, 0.001);
    }
}
