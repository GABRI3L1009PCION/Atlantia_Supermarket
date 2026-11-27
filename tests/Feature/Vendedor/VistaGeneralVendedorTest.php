<?php

namespace Tests\Feature\Vendedor;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistaGeneralVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_vendor_sees_store_setup_guide_and_new_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['name' => 'Luz Morales']);
        $user->assignRole('vendedor');
        Vendor::factory()->approved()->create(['user_id' => $user->id, 'business_name' => 'Comercial El Puerto']);

        $this->actingAs($user)->get(route('vendedor.dashboard'))
            ->assertOk()
            ->assertSee('Comercial El Puerto')
            ->assertSee('Deja lista tu tienda para vender')
            ->assertSee('Sube tus productos')
            ->assertSee('Pedidos por atender')
            ->assertSee('data-campana', false)
            ->assertSee('Ver mi tienda')
            ->assertSee('data-menu-seccion="Mi tienda"', false)
            ->assertSee('logo-atlantia-delivery.png', false)
            ->assertDontSee('Atlantia Supermarket');
    }
}
