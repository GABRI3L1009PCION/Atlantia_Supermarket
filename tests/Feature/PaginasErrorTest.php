<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Services\Vendedores\AccesoVendedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaginasErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_too_many_form_submissions_return_to_the_form_with_a_clear_notice(): void
    {
        $desde = route('password.request');

        for ($i = 0; $i < 6; $i++) {
            $this->from($desde)->post(route('password.email'), ['email' => 'nadie@example.test']);
        }

        /* En vez de una página de error, vuelve al formulario con aviso y lo escrito. */
        $this->from($desde)->post(route('password.email'), ['email' => 'nadie@example.test'])
            ->assertRedirect($desde)
            ->assertSessionHas('error', fn (string $mensaje): bool => str_contains($mensaje, 'espera') && str_contains($mensaje, 'vuelve a intentarlo'))
            ->assertSessionHasInput('email', 'nadie@example.test');
    }

    public function test_each_route_has_its_own_attempt_counter(): void
    {
        Notification::fake();
        Role::findOrCreate('vendedor', 'web');
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('vendedor');
        Vendor::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'is_approved' => true, 'application_code' => 'VND-2026-CONTADOR']);
        $enlace = app(AccesoVendedorService::class)->enlaceCrearContrasena($user);
        $seguimiento = route('vendedor.solicitud.show', 'VND-2026-CONTADOR');

        /* Se agota el límite de 3 por hora de "Enviarme otro enlace"... */
        for ($i = 0; $i < 4; $i++) {
            $this->from($seguimiento)->post(route('vendedor.solicitud.reenviar', 'VND-2026-CONTADOR'));
        }

        /* ...pero la página para crear la contraseña sigue abriendo sin problema. */
        for ($i = 0; $i < 5; $i++) {
            $this->get($enlace)->assertOk()->assertSee('Crea tu contraseña');
        }
    }

    public function test_opening_a_blocked_page_shows_readable_wait_and_retries_the_same_page(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('vendedor');
        Vendor::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'is_approved' => true]);
        $enlace = app(AccesoVendedorService::class)->enlaceCrearContrasena($user);

        for ($i = 0; $i < 20; $i++) {
            $this->get($enlace);
        }

        $respuesta = $this->get($enlace);

        $respuesta->assertStatus(429)
            ->assertSee('Hiciste muchos intentos seguidos.')
            ->assertSee('logo-atlantia-delivery.png', false)
            ->assertSee('Intentar de nuevo');

        $espera = (int) $respuesta->headers->get('Retry-After');
        $this->assertGreaterThan(0, $espera);
        $this->assertLessThanOrEqual(60, $espera);
        $respuesta->assertSee('data-retry-countdown="'.$espera.'"', false);
        $this->assertStringContainsString('data-retry-redirect="'.e($enlace).'"', $respuesta->getContent());
    }

    public function test_not_found_page_uses_current_brand(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('No encontramos la página que buscas.')
            ->assertSee('logo-atlantia-delivery.png', false)
            ->assertDontSee('Atlantia Supermarket');
    }
}
