<?php

namespace Tests\Feature\Auth;

use App\Jobs\EnviarWhatsApp;
use App\Models\User;
use App\Services\Auth\AccesoRapidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccesoRapidoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('cliente', 'web');
        Role::findOrCreate('vendedor', 'web');
    }

    private function codigoEnviado(): string
    {
        $codigo = null;
        Queue::assertPushed(EnviarWhatsApp::class, function (EnviarWhatsApp $job) use (&$codigo): bool {
            $codigo = $job->parametros[0];

            return $job->plantilla === 'codigo_acceso';
        });

        return (string) $codigo;
    }

    public function test_new_customer_signs_up_with_whatsapp_code(): void
    {
        Queue::fake();

        $this->post(route('acceso.telefono.enviar'), ['telefono' => '5555 1234'])
            ->assertRedirect(route('acceso.telefono'));

        $this->post(route('acceso.telefono.verificar'), ['codigo' => '000000', 'nombre' => 'Ana'])
            ->assertSessionHasErrors('codigo');

        $this->post(route('acceso.telefono.verificar'), ['codigo' => $this->codigoEnviado(), 'nombre' => 'Ana López'])
            ->assertRedirect();

        $usuario = User::query()->where('name', 'Ana López')->firstOrFail();
        $this->assertAuthenticatedAs($usuario);
        $this->assertTrue($usuario->hasRole('cliente'));
        $this->assertSame('55551234', $usuario->phone);
    }

    public function test_existing_customer_logs_in_with_code(): void
    {
        Queue::fake();
        $cliente = User::factory()->create(['phone' => '+502 5555-9876']);
        $cliente->assignRole('cliente');

        $this->post(route('acceso.telefono.enviar'), ['telefono' => '55559876']);
        $this->post(route('acceso.telefono.verificar'), ['codigo' => $this->codigoEnviado()]);

        $this->assertAuthenticatedAs($cliente);
    }

    public function test_staff_accounts_must_use_password(): void
    {
        Queue::fake();
        $vendedor = User::factory()->create(['phone' => '55554444']);
        $vendedor->assignRole('vendedor');

        $this->post(route('acceso.telefono.enviar'), ['telefono' => '55554444'])
            ->assertSessionHasErrors('telefono');

        Queue::assertNotPushed(EnviarWhatsApp::class);
        $this->assertGuest();
    }

    public function test_google_links_existing_customer_by_email(): void
    {
        $cliente = User::factory()->create(['email' => 'ana@example.com']);
        $cliente->assignRole('cliente');

        $usuario = app(AccesoRapidoService::class)->usuarioDeGoogle('g-123', 'Ana@example.com', 'Ana');

        $this->assertTrue($usuario->is($cliente));
        $this->assertSame('g-123', $cliente->fresh()->google_id);
    }

    public function test_login_page_shows_phone_option_and_hides_google_without_credentials(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Con mi teléfono (código por WhatsApp)')
            ->assertDontSee('Continuar con Google');
    }
}
