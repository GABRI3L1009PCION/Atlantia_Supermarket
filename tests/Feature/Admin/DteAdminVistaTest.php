<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DteAdminVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoices_page_shows_real_connection_mode(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        config(['services.infile.mock' => true]);
        $this->actingAs($admin)->get(route('admin.dte.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Facturas electrónicas (FEL)')
            ->assertSee('Modo simulado: las facturas no se envían a la SAT')
            ->assertSee('Aún no hay facturas emitidas.');

        config(['services.infile.mock' => false, 'services.infile.base_url' => 'https://certificador.infile.com.gt/api']);
        $this->actingAs($admin)->get(route('admin.dte.index', ['estado' => 'rechazado']))
            ->assertOk()
            ->assertSee('Conectado a Infile · SAT en producción')
            ->assertSee('Ninguna factura coincide con los filtros.');
    }
}
