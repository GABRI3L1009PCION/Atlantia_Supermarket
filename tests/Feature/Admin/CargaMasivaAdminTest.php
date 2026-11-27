<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CargaMasivaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_page_keeps_super_admin_panel(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.productos.importar'))
            ->assertOk()
            ->assertSee('Carga masiva')
            ->assertSee('Dueño · acceso total')
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Descargar plantilla')
            ->assertSee('¿A qué tienda van?');
    }
}
