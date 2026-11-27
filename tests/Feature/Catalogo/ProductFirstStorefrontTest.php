<?php

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFirstStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_displays_each_product_with_its_own_store_and_cart_form(): void
    {
        $first = $this->product('Banano', 'Frutas del Puerto', 4.50);
        $second = $this->product('Manzana Roja', 'Mercado de Izabal', 5.85);
        $response = $this->get(route('home'));
        $response->assertOk()->assertSee('Banano')->assertSee('Manzana Roja')->assertSee('Frutas del Puerto')->assertSee('Mercado de Izabal');
        $response->assertSee(route('comercios.index'), false)->assertSee('Tiendas afiliadas');
        $doc = new \DOMDocument;
        @$doc->loadHTML($response->getContent());
        $xpath = new \DOMXPath($doc);
        foreach ([$first, $second] as $product) {
            $article = '//article[@data-storefront-product="'.$product->id.'" and @data-vendor-id="'.$product->vendor_id.'"]';
            $this->assertSame(1, $xpath->query($article.'/div/form/input[@name="producto_id" and @value="'.$product->id.'"]')->length);
        }
    }

    public function test_search_category_price_and_stock_filters_apply_to_products(): void
    {
        $fruit = $this->category();
        $cheap = $this->product('Manzana Roja', 'Frutería', 5, $fruit->id);
        $expensive = $this->product('Manzana Verde', 'Mercado', 9, $fruit->id);
        $reserved = $this->product('Manzana Reservada', 'Otra tienda', 2, $fruit->id);
        $reserved->inventario->update(['stock_actual' => 10, 'stock_reservado' => 10]);
        $this->product('Arroz', 'Despensa', 10);
        $response = $this->get(route('catalogo.index', ['q' => 'Manzana', 'categoria_id' => $fruit->id, 'orden' => 'precio_asc', 'stock' => 1]));
        $response->assertOk()->assertSeeInOrder([$cheap->nombre, $expensive->nombre])->assertDontSee('Manzana Reservada')->assertDontSee('>Arroz<', false);
    }

    public function test_unapproved_stores_are_hidden_and_both_municipalities_show_by_default(): void
    {
        $visible = $this->product('Producto local', 'Tienda local', 10);
        $unapproved = $this->product('Producto oculto', 'No aprobada', 10);
        $unapproved->vendor->update(['is_approved' => false, 'status' => 'pending']);
        $other = $this->product('Producto otro municipio', 'Otra zona', 10);
        $other->vendor->update(['municipio' => 'Santo Tomas']);
        $this->get(route('home'))->assertOk()->assertSee($visible->nombre)->assertDontSee($unapproved->nombre)->assertSee($other->nombre);
        $this->withSession(['cliente_municipio' => 'Santo Tomas'])->get(route('home'))->assertOk()->assertSee($visible->nombre)->assertSee($other->nombre);
        $this->get(route('home', ['municipio' => 'Santo Tomas']))->assertOk()->assertSee($other->nombre)->assertDontSee($visible->nombre);
    }

    public function test_parent_category_includes_products_from_active_subcategories(): void
    {
        $parent = $this->category();
        $child = $this->category();
        $child->update(['parent_id' => $parent->id]);
        $product = $this->product('Fruta de subcategoría', 'Frutería local', 8, $child->id);
        $this->get(route('catalogo.index', ['categoria_id' => $parent->id]))
            ->assertOk()->assertSee($product->nombre);
    }

    public function test_guest_can_save_a_product_and_see_it_selected_after_reload(): void
    {
        $product = $this->product('Favorito local', 'Tienda favoritos', 10);
        $this->from(route('home'))->post(route('cliente.wishlist.toggle', $product->uuid))->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()->assertSee('aria-pressed="true"', false);
    }

    private function product(string $name, string $store, float $price, ?int $category = null): Producto
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => $store, 'municipio' => 'Puerto Barrios']);
        $product = Producto::factory()->publicado()->create([
            'nombre' => $name, 'vendor_id' => $vendor->id, 'precio_base' => $price, 'precio_oferta' => null,
            'categoria_id' => $category ?? $this->category()->id,
        ]);
        Inventario::factory()->create(['producto_id' => $product->id, 'stock_actual' => 10, 'stock_reservado' => 0]);

        return $product;
    }

    private function category(): Categoria
    {
        return Categoria::query()->create(['nombre' => 'Categoría '.uniqid(), 'slug' => 'categoria-'.uniqid(), 'is_active' => true, 'orden' => 1]);
    }
}
