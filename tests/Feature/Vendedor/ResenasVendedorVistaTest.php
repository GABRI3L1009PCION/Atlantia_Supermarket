<?php

namespace Tests\Feature\Vendedor;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResenasVendedorVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_page_shows_real_stats_and_no_fake_buttons(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => 'Crema Nivea']);
        foreach ([[5, true, 'Me encantó'], [4, true, 'Buena'], [2, false, 'Llegó tarde']] as [$estrellas, $aprobada, $texto]) {
            $cliente = User::factory()->create(['name' => 'María López Pérez']);
            $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'cliente_id' => $cliente->id]);
            Resena::query()->create([
                'uuid' => (string) Str::uuid(), 'producto_id' => $producto->id, 'cliente_id' => $cliente->id,
                'pedido_id' => $pedido->id, 'calificacion' => $estrellas, 'contenido' => $texto, 'aprobada' => $aprobada,
            ]);
        }

        $this->actingAs($vendor->user)->get(route('vendedor.resenas.index'))
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('2 reseñas publicadas')
            ->assertSee('En revisión')
            ->assertSee('Me encantó')
            ->assertSee('María L.')
            ->assertDontSee('María López Pérez')
            ->assertDontSee('vs. mes anterior')
            ->assertSee('Responder')
            ->assertSee('Podrás responder cuando Atlantia la publique.')
            ->assertDontSee('Exportar resenas');
    }

    public function test_seller_replies_and_reply_shows_on_product_page_only_for_published_reviews(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $vendor->user->assignRole('vendedor');
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id]);
        $crear = function (int $estrellas, bool $aprobada, string $texto) use ($vendor, $producto): Resena {
            $cliente = User::factory()->create(['name' => 'Ana Gómez']);
            $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'cliente_id' => $cliente->id]);

            return Resena::query()->create([
                'uuid' => (string) Str::uuid(), 'producto_id' => $producto->id, 'cliente_id' => $cliente->id,
                'pedido_id' => $pedido->id, 'calificacion' => $estrellas, 'contenido' => $texto, 'aprobada' => $aprobada,
            ]);
        };
        $publicada = $crear(5, true, 'Excelente producto');
        $enRevision = $crear(1, false, 'Texto aún sin revisar');

        $this->actingAs($vendor->user)
            ->post(route('vendedor.resenas.responder', $publicada), ['respuesta' => '¡Gracias por tu compra, Ana!'])
            ->assertSessionHasNoErrors();
        $this->actingAs($vendor->user)
            ->post(route('vendedor.resenas.responder', $enRevision), ['respuesta' => 'Hola'])
            ->assertStatus(422);

        $otro = Vendor::factory()->approved()->create();
        $otro->user->assignRole('vendedor');
        $this->actingAs($otro->user)
            ->post(route('vendedor.resenas.responder', $publicada), ['respuesta' => 'No es mío'])
            ->assertForbidden();

        auth()->logout();
        $this->get(route('productos.show', $producto->uuid))
            ->assertOk()
            ->assertSee('Opiniones de clientes')
            ->assertSee('Excelente producto')
            ->assertSee('Ana G.')
            ->assertSee('Respuesta de Comercial El Puerto')
            ->assertSee('¡Gracias por tu compra, Ana!')
            ->assertDontSee('Texto aún sin revisar');
    }
}
