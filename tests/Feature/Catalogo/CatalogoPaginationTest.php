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
        $response->assertSee('data-product-carousel', false);
        $response->assertSee('data-carousel-track', false);
        $this->assertSame(3, substr_count($response->getContent(), 'Agregar'));
    }

    public function test_comercio_catalog_filters_real_products_by_search_and_offer(): void
    {
        $vendor = Vendor::factory()->approved()->create([
            'business_name' => 'Mercado del Puerto',
            'slug' => 'mercado-del-puerto',
        ]);

        Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'nombre' => 'Tomate fresco',
            'precio_base' => 12,
            'precio_oferta' => 9,
        ]);
        Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'nombre' => 'Arroz blanco',
            'precio_base' => 18,
            'precio_oferta' => null,
        ]);

        $response = $this->get(route('comercios.show', [
            'vendor' => $vendor->slug,
            'q' => 'Tomate',
            'ofertas' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Tomate fresco', false);
        $response->assertDontSee('Arroz blanco', false);
        $response->assertSee('1 resultado(s)', false);
    }

    public function test_marketplace_mobile_navigation_and_product_detail_use_shared_layout(): void
    {
        $vendor = Vendor::factory()->approved()->create([
            'business_name' => 'Tienda Responsive',
            'slug' => 'tienda-responsive',
        ]);
        $producto = Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'nombre' => 'Producto Responsive',
            'precio_base' => 24.50,
        ]);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('Navegacion inferior', false);
        $home->assertSee('Explora por Categoria', false);
        $home->assertSee('Comercios disponibles', false);

        $detail = $this->get(route('productos.show', ['producto' => $producto->uuid]));
        $detail->assertOk();
        $detail->assertSee('Producto Responsive', false);
        $detail->assertSee('Tienda Responsive', false);
        $detail->assertSee('Agregar al carrito', false);
        $detail->assertSee('Navegacion inferior', false);
    }

    public function test_cliente_location_selector_stores_active_municipio(): void
    {
        $response = $this
            ->from(route('home'))
            ->post(route('cliente.ubicacion.store'), ['municipio' => 'Santo Tomas']);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('cliente_municipio', 'Santo Tomas');

        $this
            ->withSession(['cliente_municipio' => 'Santo Tomas'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Santo Tomas', false);
    }

    public function test_cliente_location_selector_rejects_non_operational_municipio(): void
    {
        $response = $this
            ->from(route('home'))
            ->post(route('cliente.ubicacion.store'), ['municipio' => 'Morales']);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors('municipio');
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
