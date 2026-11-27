<?php

namespace Tests\Feature\Vendedor;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortadaTiendaTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $user->id, 'cover_path' => null]);

        return [$user, $vendor];
    }

    public function test_vendor_uploads_cover_logo_and_description_and_store_shows_them(): void
    {
        Storage::fake('public');
        [$user, $vendor] = $this->vendedor();

        $this->actingAs($user)->get(route('vendedor.apariencia.edit'))
            ->assertOk()
            ->assertSee('Perfil de la tienda');

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), [
            'portada' => UploadedFile::fake()->image('portada.jpg', 1800, 300),
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
            'descripcion' => 'Abarrotes y limpieza con entrega a domicilio.',
            'categorias' => ['cosmeticos', 'abarrotes'],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $vendor->refresh();
        $this->assertStringStartsWith('comercios/'.$vendor->uuid.'/portada-', $vendor->cover_path);
        Storage::disk('public')->assertExists($vendor->cover_path);
        Storage::disk('public')->assertExists($vendor->logo_path);
        $this->assertSame('Abarrotes y limpieza con entrega a domicilio.', $vendor->descripcion);

        $this->get(route('comercios.show', $vendor->slug))
            ->assertOk()
            ->assertSee('/storage/'.$vendor->cover_path, false)
            ->assertSee('Abarrotes y limpieza con entrega a domicilio.')
            ->assertSee('Belleza y cuidado personal · Abarrotes y supermercado');

        $this->assertSame(['cosmeticos', 'abarrotes'], $vendor->categorias_negocio);
        $this->assertSame('cosmeticos', $vendor->business_category);
    }

    public function test_vendor_must_choose_between_one_and_three_categories(): void
    {
        Storage::fake('public');
        [$user] = $this->vendedor();

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), ['categorias' => []])
            ->assertSessionHasErrors(['categorias' => 'Elige al menos una categoría para tu tienda.']);

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), ['categorias' => ['cosmeticos', 'abarrotes', 'farmacia', 'mascotas']])
            ->assertSessionHasErrors(['categorias' => 'Puedes elegir hasta 3 categorías.']);

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), ['categorias' => ['inventada']])
            ->assertSessionHasErrors('categorias.0');
    }

    public function test_store_cards_show_available_stock(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        foreach (['Crema Nivea' => [30, 6], 'Agua micelar' => [4, 1], 'Labial rojo' => [2, 2]] as $nombre => [$actual, $reservado]) {
            $producto = \App\Models\Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'nombre' => $nombre]);
            \App\Models\Inventario::factory()->create(['producto_id' => $producto->id, 'stock_actual' => $actual, 'stock_reservado' => $reservado]);
        }

        $this->get(route('comercios.show', $vendor->slug))
            ->assertOk()
            ->assertSee('24 disponibles')
            ->assertSee('¡Quedan 3!')
            ->assertSee('Agotado');
    }

    public function test_store_shows_category_label_not_internal_key(): void
    {
        $vendor = Vendor::factory()->approved()->create(['business_category' => 'ropa_accesorios', 'categorias_negocio' => null]);

        $this->get(route('comercios.show', $vendor->slug))
            ->assertOk()
            ->assertSee('Ropa y accesorios')
            ->assertDontSee('ropa_accesorios');
    }

    public function test_replacing_and_removing_cover_deletes_old_file(): void
    {
        Storage::fake('public');
        [$user, $vendor] = $this->vendedor();

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), [
            'portada' => UploadedFile::fake()->image('a.jpg', 1200, 400),
            'categorias' => ['abarrotes'],
        ]);
        $primera = $vendor->refresh()->cover_path;

        $this->actingAs($user)->put(route('vendedor.apariencia.update'), [
            'portada' => UploadedFile::fake()->image('b.jpg', 1200, 400),
            'categorias' => ['abarrotes'],
        ]);
        Storage::disk('public')->assertMissing($primera);
        $segunda = $vendor->refresh()->cover_path;

        $this->actingAs($user)->delete(route('vendedor.apariencia.portada.destroy'))->assertRedirect();
        Storage::disk('public')->assertMissing($segunda);
        $this->assertNull($vendor->refresh()->cover_path);
    }

    public function test_too_small_cover_is_rejected_with_clear_message(): void
    {
        Storage::fake('public');
        [$user] = $this->vendedor();

        $this->actingAs($user)->from(route('vendedor.apariencia.edit'))->put(route('vendedor.apariencia.update'), [
            'portada' => UploadedFile::fake()->image('chica.jpg', 300, 100),
            'categorias' => ['abarrotes'],
        ])->assertSessionHasErrors(['portada' => 'La portada es muy pequeña: usa una imagen de 1800 × 300 píxeles.']);
    }

    public function test_store_page_has_no_duplicate_global_search(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $this->get(route('comercios.show', $vendor->slug))
            ->assertOk()
            ->assertDontSee('Buscar en todo el supermercado', false)
            ->assertSee('Buscar en '.$vendor->business_name, false);
    }
}
