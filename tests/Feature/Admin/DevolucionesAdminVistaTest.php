<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DevolucionesAdminVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_page_shows_summary_and_history_tab(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.devoluciones.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Reembolsado este mes')
            ->assertSee('No hay devoluciones por resolver');

        $this->actingAs($admin)->get(route('admin.devoluciones.index', ['vista' => 'resueltas']))
            ->assertOk()
            ->assertSee('Aún no has resuelto devoluciones.');
    }
}
