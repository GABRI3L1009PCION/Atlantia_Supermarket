<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Pruebas de proteccion CSRF en formularios web.
 */
class CsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withMiddleware();
    }

    public function testFormularioLoginIncluyeTokenCsrf(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function testFormularioRegistroIncluyeTokenCsrf(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function testFormularioCheckoutIncluyeTokenCsrf(): void
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)
            ->get(route('cliente.checkout.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function testFormularioAdminIncluyeTokenCsrf(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function testFormularioVendedorIncluyeTokenCsrf(): void
    {
        $user = User::factory()->vendedor()->create();
        $user->assignRole('vendedor');
        Vendor::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('vendedor.productos.index'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function testWebhooksSiguenExcluidosDeCsrf(): void
    {
        $reflection = new ReflectionClass(VerifyCsrfToken::class);
        $except = $reflection->getProperty('except');
        $except->setAccessible(true);

        /** @var array<int, string> $paths */
        $paths = $except->getValue(app(VerifyCsrfToken::class));

        $this->assertContains('webhooks/pasarela-pago', $paths);
        $this->assertContains('webhooks/certificador-fel', $paths);
        $this->assertContains('webhooks/courier-externo', $paths);
        $this->assertContains('webhooks/ml-service', $paths);
    }
}
