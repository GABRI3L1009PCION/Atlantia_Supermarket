<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de proteccion CSRF en formularios web.
 */
class CsrfTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura datos base de seguridad.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Bloquea login sin token CSRF.
     */
    public function testFormularioLoginSinCsrfRetorna419(): void
    {
        $this->post(route('login.store'), [
            'email' => 'nadie@atlantia.test',
            'password' => 'Atlantia2026!',
        ])->assertStatus(419);
    }

    /**
     * Bloquea registro sin token CSRF.
     */
    public function testFormularioRegistroSinCsrfRetorna419(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Cliente Atlantia',
            'email' => 'cliente@atlantia.test',
            'phone' => '+50255123344',
            'password' => 'Atlantia2026!',
            'password_confirmation' => 'Atlantia2026!',
            'role' => 'cliente',
            'acepta_terminos' => true,
            'acepta_privacidad' => true,
        ])->assertStatus(419);
    }

    /**
     * Bloquea checkout sin token CSRF.
     */
    public function testFormularioCheckoutSinCsrfRetorna419(): void
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)
            ->post(route('cliente.checkout.store'), [
                'direccion_id' => 1,
                'metodo_pago' => 'efectivo',
                'acepta_terminos_checkout' => true,
            ])
            ->assertStatus(419);
    }

    /**
     * Bloquea alta administrativa sin token CSRF.
     */
    public function testFormularioAdminSinCsrfRetorna419(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [])
            ->assertStatus(419);
    }

    /**
     * Bloquea creacion de productos de vendedor sin token CSRF.
     */
    public function testFormularioVendedorSinCsrfRetorna419(): void
    {
        $user = User::factory()->vendedor()->create();
        $user->assignRole('vendedor');
        Vendor::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('vendedor.productos.store'), [])
            ->assertStatus(419);
    }
}
