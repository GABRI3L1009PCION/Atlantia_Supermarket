<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NominasVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_page_shows_inline_form_and_salary_warning(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.nominas.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Nómina de empleados')
            ->assertSee('data-nueva-nomina', false)
            ->assertSee('Aún no tienes empleados activos.')
            ->assertSee('Aún no hay nóminas registradas.')
            ->assertDontSee('data-open-create-modal', false);
    }
}
