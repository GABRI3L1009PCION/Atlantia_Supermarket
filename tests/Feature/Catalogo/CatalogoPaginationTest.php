<?php

namespace Tests\Feature\Catalogo;

use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Busqueda\MeilisearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogoPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_home_does_not_render_global_product_catalog(): void
    {
        Producto::factory()
            ->count(50)
            ->publicado()
            ->sequence(fn ($sequence): array => [
                'publicado_at' => now()->subMinutes($sequence->index),
            ])
            ->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Listo para tu primer pedido?', false);
        $response->assertDontSee('Pagina 1 de 2', false);
        $response->assertDontSee('Agregar al carrito', false);
    }

    public function test_comercio_shows_published_products_for_selected_vendor(): void
    {
        $vendor = Vendor::factory()->approved()->create([
            'business_name' => 'Atlantia Supermarket',
            'slug' => 'atlantia-supermarket',
        ]);

        Producto::factory()
            ->count(3)
            ->publicado()
            ->sequence(
                ['nombre' => 'Arroz Atlantia'],
                ['nombre' => 'Frijol Atlantia'],
                ['nombre' => 'Cafe Atlantia'],
            )
            ->create(['vendor_id' => $vendor->id]);

        $response = $this->get(route('comercios.show', ['vendor' => $vendor->slug]));

        $response->assertOk();
        $response->assertSee('Atlantia Supermarket', false);
        $response->assertSee('Arroz Atlantia', false);
        $response->assertSee('Frijol Atlantia', false);
        $response->assertSee('Cafe Atlantia', false);
        $this->assertSame(3, substr_count($response->getContent(), 'Agregar'));
    }

    public function test_cliente_location_selector_stores_active_municipio(): void
    {
        $response = $this
            ->from(route('home'))
            ->post(route('cliente.ubicacion.store'), ['municipio' => 'Morales']);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('cliente_municipio', 'Morales');

        $this
            ->withSession(['cliente_municipio' => 'Morales'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Morales', false);
    }

    public function test_comercios_use_selected_municipio_from_session(): void
    {
        $morales = Vendor::factory()->approved()->create([
            'business_name' => 'Tienda Morales',
            'slug' => 'tienda-morales',
            'municipio' => 'Morales',
        ]);
        $puerto = Vendor::factory()->approved()->create([
            'business_name' => 'Tienda Puerto',
            'slug' => 'tienda-puerto',
            'municipio' => 'Puerto Barrios',
        ]);

        Producto::factory()->publicado()->create(['vendor_id' => $morales->id]);
        Producto::factory()->publicado()->create(['vendor_id' => $puerto->id]);

        $response = $this
            ->withSession(['cliente_municipio' => 'Morales'])
            ->get(route('comercios.index'));

        $response->assertOk();
        $response->assertSee('Tienda Morales', false);
        $response->assertDontSee('Tienda Puerto', false);
    }

    public function test_catalogo_search_service_honors_requested_page(): void
    {
        Producto::factory()
            ->count(50)
            ->publicado()
            ->sequence(fn ($sequence): array => [
                'publicado_at' => now()->subMinutes($sequence->index),
            ])
            ->create();

        $results = app(MeilisearchService::class)->search([
            'per_page' => 48,
            'page' => 2,
        ]);

        $this->assertSame(2, $results['pagination']['current_page']);
        $this->assertSame(48, $results['pagination']['per_page']);
        $this->assertSame(2, $results['items']->count());
    }
}
