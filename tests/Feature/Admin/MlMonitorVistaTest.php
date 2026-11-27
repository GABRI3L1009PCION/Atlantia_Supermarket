<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MlMonitorVistaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_monitor_shows_plain_status_when_active(): void
    {
        config(['atlantia.features.advanced_ml_automation' => true]);

        $this->actingAs($this->admin())->get(route('admin.ml.monitor'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Monitor de inteligencia artificial')
            ->assertSee('IA activada')
            ->assertSee('Todo funcionando bien.')
            ->assertSee('Métricas técnicas')
            ->assertDontSee('Actualizar umbral');
    }

    public function test_monitor_offers_activation_when_off(): void
    {
        config(['atlantia.features.advanced_ml_automation' => false]);

        $this->actingAs($this->admin())->get(route('admin.ml.monitor'))
            ->assertOk()
            ->assertSee('La IA está apagada.')
            ->assertSee('Activar IA');
    }
}
