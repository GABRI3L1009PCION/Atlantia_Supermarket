<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de acceso y restricciones del panel administrativo.
 */
class AdminPanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Permite acceso al dashboard administrativo para admin.
     */
    public function testAdminCanAccessAdminDashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /**
     * Permite acceso al dashboard administrativo para super admin.
     */
    public function testSuperAdminCanAccessAdminDashboard(): void
    {
        $superAdmin = User::factory()->admin()->create(['email' => 'root.panel@atlantia.test']);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /**
     * Bloquea acceso al dashboard administrativo para clientes.
     */
    public function testClienteCannotAccessAdminDashboard(): void
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $response = $this->actingAs($cliente)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    /**
     * Impide que un admin operativo cree otra cuenta admin.
     */
    public function testAdminCannotCreateAnotherAdminUser(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $response = $this->from(route('admin.usuarios.index'))
            ->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Nuevo Administrador',
                'email' => 'nuevo.admin@atlantia.test',
                'phone' => '+502 7812-9911',
                'password' => 'AtlantiaAdmin2026!',
                'password_confirmation' => 'AtlantiaAdmin2026!',
                'status' => 'active',
                'role' => 'admin',
            ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'nuevo.admin@atlantia.test']);
    }

    /**
     * Permite que el super admin cree una cuenta admin.
     */
    public function testSuperAdminCanCreateAdminUser(): void
    {
        $superAdmin = User::factory()->admin()->create(['email' => 'root@atlantia.test']);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.usuarios.store'), [
            'name' => 'Administrador Operativo',
            'email' => 'admin.operativo@atlantia.test',
            'phone' => '+502 7833-1122',
            'password' => 'AtlantiaAdmin2026!',
            'password_confirmation' => 'AtlantiaAdmin2026!',
            'status' => 'active',
            'role' => 'admin',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'admin.operativo@atlantia.test']);

        $usuario = User::query()->where('email', 'admin.operativo@atlantia.test')->firstOrFail();
        $this->assertTrue($usuario->hasRole('admin'));
    }

    /**
     * Bloquea a un admin operativo cuando intenta editar otro admin.
     */
    public function testAdminCannotUpdateExistingAdminUser(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'operaciones@atlantia.test']);
        $admin->assignRole('admin');

        $target = User::factory()->admin()->create(['email' => 'segundo.admin@atlantia.test']);
        $target->assignRole('admin');

        $response = $this->actingAs($admin)->put(route('admin.usuarios.update', $target->uuid), [
            'name' => 'Admin Editado',
            'email' => 'segundo.admin@atlantia.test',
            'phone' => '+502 7999-1100',
            'status' => 'active',
            'roles' => ['cliente'],
        ]);

        $response->assertForbidden();
    }
}
