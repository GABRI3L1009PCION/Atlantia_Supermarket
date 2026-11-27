<?php

namespace Tests\Feature\Cliente;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CarritoCantidadAutomaticaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: CarritoItem}
     */
    private function carritoCon(int $stock, int $cantidad = 1): array
    {
        $this->seed(RolePermissionSeeder::class);
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');
        $vendor = Vendor::factory()->approved()->create();
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'precio_base' => 149, 'precio_oferta' => null]);
        Inventario::factory()->create(['producto_id' => $producto->id, 'stock_actual' => $stock, 'stock_reservado' => 0]);

        $carrito = Carrito::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'session_id' => null,
            'estado' => 'activo',
            'expira_at' => now()->addDays(2),
        ]);
        $item = CarritoItem::query()->create([
            'carrito_id' => $carrito->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario_snapshot' => 149,
        ]);

        return [$cliente, $item];
    }

    public function test_quantity_saves_automatically_and_page_shows_new_total(): void
    {
        [$cliente, $item] = $this->carritoCon(10);

        $this->actingAs($cliente)
            ->putJson(route('cliente.carrito.items.update', $item), ['cantidad' => 3])
            ->assertOk()
            ->assertJson(['cantidad' => 3]);

        $this->actingAs($cliente)->get(route('cliente.carrito.index'))
            ->assertOk()
            ->assertSee('data-carrito', false)
            ->assertSee('data-cantidad-form', false)
            ->assertSee('Máx. 10')
            ->assertSee('447.00');
    }

    public function test_more_than_available_stock_returns_clear_message(): void
    {
        [$cliente, $item] = $this->carritoCon(2);

        $this->actingAs($cliente)
            ->putJson(route('cliente.carrito.items.update', $item), ['cantidad' => 5])
            ->assertStatus(422)
            ->assertJson(['message' => 'Solo hay 2 unidades disponibles de este producto.', 'disponible' => 2]);

        $this->assertSame(1, $item->refresh()->cantidad);
    }
}
