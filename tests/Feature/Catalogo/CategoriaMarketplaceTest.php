<?php

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_page_uses_root_categories_and_counts_published_child_products(): void
    {
        $parent = Categoria::query()->create([
            'nombre' => 'Alimentos frescos',
            'slug' => 'alimentos-frescos',
            'orden' => 1,
            'is_active' => true,
        ]);
        $child = Categoria::query()->create([
            'parent_id' => $parent->id,
            'nombre' => 'Frutas tropicales',
            'slug' => 'frutas-tropicales',
            'orden' => 1,
            'is_active' => true,
        ]);

        Producto::factory()->publicado()->create(['categoria_id' => $child->id]);

        $response = $this->get(route('categorias.index'));

        $response->assertOk();
        $response->assertSee('Alimentos frescos', false);
        $response->assertSee('1 producto', false);
        $response->assertSee(route('comercios.index', [
            'categoria' => $parent->id,
            'municipio' => 'Puerto Barrios',
        ]));
        $response->assertDontSee('Frutas tropicales', false);
    }

    public function test_parent_category_filter_includes_vendors_with_products_in_active_children(): void
    {
        $parent = Categoria::query()->create([
            'nombre' => 'Despensa',
            'slug' => 'despensa',
            'orden' => 1,
            'is_active' => true,
        ]);
        $child = Categoria::query()->create([
            'parent_id' => $parent->id,
            'nombre' => 'Granos',
            'slug' => 'granos',
            'orden' => 1,
            'is_active' => true,
        ]);
        $vendor = Vendor::factory()->approved()->create([
            'business_name' => 'Mercado de granos',
            'municipio' => 'Puerto Barrios',
        ]);

        Producto::factory()->publicado()->create([
            'categoria_id' => $child->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->get(route('comercios.index', [
            'categoria' => $parent->id,
            'municipio' => 'Puerto Barrios',
        ]));

        $response->assertOk();
        $response->assertSee('Mercado de granos', false);
    }
}
