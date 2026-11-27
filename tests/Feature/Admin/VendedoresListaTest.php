<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendedoresListaTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendors_page_lists_each_store_as_compact_row(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');
        Vendor::factory()->create(['business_name' => 'Moda Urbana Prueba', 'status' => 'pending']);
        Vendor::factory()->approved()->create(['business_name' => 'Tienda Aprobada Prueba']);

        $this->actingAs($admin)->get(route('admin.vendedores.index'))
            ->assertOk()
            ->assertSee('Vendedores')
            ->assertSee('Moda Urbana Prueba')
            ->assertSee('Tienda Aprobada Prueba')
            ->assertSee('Revisar')
            ->assertSee('data-vendor-row', false)
            ->assertDontSee('Atlantia Supermarket</p>', false);
    }
}
