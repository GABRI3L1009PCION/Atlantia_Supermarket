<?php

namespace Tests\Feature\Cliente;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCuentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_sees_account_menu_and_section_bar(): void
    {
        $cliente = User::factory()->create(['name' => 'María López']);
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)->get(route('cliente.direcciones.index'))
            ->assertOk()
            ->assertSee('data-menu-cuenta', false)
            ->assertSee('data-cuenta-cliente-nav', false)
            ->assertSee('Mis pedidos')
            ->assertSee('Mis direcciones')
            ->assertSee('Mi saldo')
            ->assertSee('Cerrar sesión')
            ->assertSee('aria-current="page"', false);
    }

    public function test_account_bar_does_not_show_on_store_pages(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)->get(route('home'))
            ->assertOk()
            ->assertSee('data-menu-cuenta', false)
            ->assertDontSee('data-cuenta-cliente-nav', false);
    }

    public function test_seller_menu_links_to_their_panel(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        Vendor::factory()->approved()->create(['user_id' => $vendedor->id]);

        $this->actingAs($vendedor)->get(route('home'))
            ->assertOk()
            ->assertSee('Ir a mi panel')
            ->assertSee(route('vendedor.dashboard'), false)
            ->assertDontSee('Mis direcciones');
    }

    public function test_guest_sees_sign_in_instead_of_account_menu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-menu-cuenta', false);
    }
}
