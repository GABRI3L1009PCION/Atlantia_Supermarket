<?php

namespace Tests\Feature\Catalogo;

use App\Models\Inventario;
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

    public function test_home_renders_paginated_products_from_affiliated_stores(): void
    {
        $vendor = Vendor::factory()->approved()->create(['municipio' => 'Puerto Barrios']);
        Producto::factory()
            ->count(50)
            ->publicado()
            ->sequence(fn ($sequence): array => [
                'publicado_at' => now()->subMinutes($sequence->index),
            ])
            ->create(['vendor_id' => $vendor->id, 'precio_oferta' => null])
            ->each(fn (Producto $producto) => Inventario::query()->create([
                'producto_id' => $producto->id,
                'stock_actual' => 12,
                'stock_reservado' => 0,
                'stock_minimo' => 1,
                'stock_maximo' => 100,
                'ultima_actualizacion' => now(),
            ]));

        $agotado = Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'nombre' => 'Producto agotado de prueba',
            'publicado_at' => now(),
        ]);
        Inventario::query()->create([
            'producto_id' => $agotado->id,
            'stock_actual' => 3,
            'stock_reservado' => 3,
            'stock_minimo' => 1,
            'stock_maximo' => 100,
            'ultima_actualizacion' => now(),
        ]);

        $response = $this->get(route('home', ['per_page' => 48]));

        $response->assertOk();
        $response->assertSee('Productos disponibles', false);
        $response->assertSee('Mostrando 48 de 50 productos', false);
        $response->assertSee('Ver más productos', false);
        $response->assertSee('page=2', false);
        $response->assertSee('12 disponibles', false);
        $response->assertDontSee('Producto agotado de prueba', false);
        $response->assertDontSee('Sin existencias', false);
        $response->assertDontSee('Solo disponibles', false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $this->assertSame(48, (new \DOMXPath($document))->query('//article[@data-storefront-product]')->length);
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
            ->create(['vendor_id' => $vendor->id, 'precio_oferta' => null]);

        $response = $this->get(route('comercios.show', ['vendor' => $vendor->slug]));

        $response->assertOk();
        $response->assertSee('Atlantia Supermarket', false);
        $response->assertSee('Arroz Atlantia', false);
        $response->assertSee('Frijol Atlantia', false);
        $response->assertSee('Cafe Atlantia', false);
        $response->assertSee('data-product-grid', false);
        $response->assertSee('Todos los productos', false);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $productCards = (new \DOMXPath($document))->query('//article[@data-product-card]');

        $this->assertSame(3, $productCards->length);
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
        $response->assertSee('Resultados', false);
        $response->assertSee('Quitar filtros', false);
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
        $home->assertSee('Explora por Categoría', false);
        $home->assertSee('Productos disponibles', false);
        $home->assertSee('Tiendas afiliadas', false);

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

    public function test_comercios_filtran_por_municipio_solicitado_y_por_defecto_muestran_todos(): void
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

        $response = $this->get(route('comercios.index', ['municipio' => 'Morales']));

        $response->assertOk();
        $response->assertSee('Tienda Morales', false);
        $response->assertDontSee('Tienda Puerto', false);

        /* Una seleccion antigua guardada en sesion ya no oculta comercios. */
        $this->withSession(['cliente_municipio' => 'Morales'])
            ->get(route('comercios.index'))
            ->assertOk()
            ->assertSee('Tienda Morales', false)
            ->assertSee('Tienda Puerto', false);
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
