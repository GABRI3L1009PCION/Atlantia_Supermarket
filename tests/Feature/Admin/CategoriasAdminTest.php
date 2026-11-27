<?php

namespace Tests\Feature\Admin;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoriasAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_page_lists_each_category_with_product_count(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');
        $producto = Producto::factory()->create();
        $categoria = $producto->categoria;
        $categoria->update(['nombre' => 'Abarrotes Prueba', 'parent_id' => null]);

        $this->actingAs($admin)->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Categorías')
            ->assertSee('Abarrotes Prueba')
            ->assertSee('Nueva categoría')
            ->assertSee('data-category-card', false)
            ->assertDontSee('Atlantia Supermarket');
    }
}
