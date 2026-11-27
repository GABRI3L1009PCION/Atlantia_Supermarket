<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbrirNotificacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearNotificacion(User $user, string $url): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\Notifications\NuevaSolicitudVendedorNotification',
            'data' => ['title' => 'Nueva solicitud para vender', 'message' => 'Prueba', 'url' => $url],
        ]);

        return $id;
    }

    public function test_open_uses_only_the_path_when_saved_with_another_domain(): void
    {
        $user = User::factory()->create();
        $id = $this->crearNotificacion($user, 'http://192.168.0.2:8000/admin/vendedores?status=pending');

        $this->actingAs($user)->get(route('notificaciones.open', $id))
            ->assertRedirect('/admin/vendedores?status=pending');

        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    public function test_open_never_redirects_outside_the_site(): void
    {
        $user = User::factory()->create();
        $externa = $this->crearNotificacion($user, '//sitio-malicioso.test/robar');

        $destino = $this->actingAs($user)->get(route('notificaciones.open', $externa))
            ->assertRedirect()
            ->headers->get('Location');

        /* Se descarta el dominio externo: el destino queda dentro de este sitio. */
        $this->assertStringNotContainsString('sitio-malicioso', (string) $destino);
        $this->assertStringStartsWith(url('/'), (string) $destino);
    }
}
