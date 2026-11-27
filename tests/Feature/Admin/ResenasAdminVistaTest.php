<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResenasAdminVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_page_shows_compact_moderation_inbox(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.resenas.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Reseñas')
            ->assertSee('Por revisar')
            ->assertSee('Calificación promedio')
            ->assertSee('Aún no hay reseñas.')
            ->assertSee('id="resenas-lote"', false)
            ->assertDontSee('Flagged ML');

        $this->actingAs($admin)->get(route('admin.resenas.index', ['flagged_ml' => '1']))
            ->assertOk()
            ->assertSee('Ninguna reseña coincide.');
    }
}
