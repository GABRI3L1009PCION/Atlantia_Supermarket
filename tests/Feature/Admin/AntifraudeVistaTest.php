<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AntifraudeVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fraud_page_shows_readable_inbox(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.antifraude.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Antifraude')
            ->assertSee('Motivo más frecuente')
            ->assertSee('No hay alertas de fraude.')
            ->assertSee('id="alertas-lote"', false)
            ->assertDontSee('ej. validacion_manual');

        $this->actingAs($admin)->get(route('admin.antifraude.index', ['riesgo_min' => '0.8']))
            ->assertOk()
            ->assertSee('Ninguna alerta coincide.');
    }
}
