<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MlReentrenamientoVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_page_shows_guided_form(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.ml.reentrenamiento.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Reentrenar modelos')
            ->assertSee('Pronóstico de demanda')
            ->assertSee('Probar antes de usarlo')
            ->assertSee('name="usar_staging" value="0"', false)
            ->assertSee('Aún no hay entrenamientos.');
    }
}
