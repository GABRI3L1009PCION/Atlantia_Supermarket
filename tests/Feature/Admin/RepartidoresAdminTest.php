<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepartidoresAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_couriers_page_shows_summary_and_filters_by_search(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('repartidor', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');
        /* Correos fijos: la búsqueda también revisa el correo y uno aleatorio podía contener "carlos". */
        User::factory()->create(['name' => 'Carlos Moto', 'email' => 'moto@example.test'])->assignRole('repartidor');
        User::factory()->create(['name' => 'Luis Bici', 'email' => 'bici@example.test'])->assignRole('repartidor');

        $this->actingAs($admin)->get(route('admin.repartidores.index'))
            ->assertOk()
            ->assertSee('Disponibles ahora')
            ->assertSee('Entregas del mes')
            ->assertSee('Carlos Moto')
            ->assertSee('Luis Bici')
            ->assertSee('Activa');

        $this->actingAs($admin)->get(route('admin.repartidores.index', ['q' => 'Carlos']))
            ->assertOk()
            ->assertSee('Carlos Moto')
            ->assertDontSee('Luis Bici');
    }
}
