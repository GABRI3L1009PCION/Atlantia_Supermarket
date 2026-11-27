<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComisionesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_commissions_page_shows_compact_layout(): void
    {
        $this->actingAs($this->admin())->get(route('admin.comisiones.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Comisiones')
            ->assertSee('Aún no hay comisiones calculadas.')
            ->assertSee('Mayor saldo')
            ->assertDontSee('data-open-create-modal', false);
    }

    public function test_month_picker_filters_by_period(): void
    {
        $this->actingAs($this->admin())->get(route('admin.comisiones.index', ['periodo' => '2026-03']))
            ->assertOk()
            ->assertSee('value="2026-03"', false)
            ->assertSee('Calcular marzo')
            ->assertSee('Ninguna comisión coincide con los filtros.');
    }
}
