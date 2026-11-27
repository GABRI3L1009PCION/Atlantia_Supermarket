<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntregasExternasVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_deliveries_open_inside_super_admin_panel(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('repartidor', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.entregas-externas.index'))
            ->assertOk()
            ->assertSee('Entregas externas')
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Nueva entrega')
            ->assertSee('Recoger en')
            ->assertDontSee('Lat recogida');
    }
}
