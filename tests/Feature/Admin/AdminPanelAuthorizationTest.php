<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryZone;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
     * Permite abrir la pantalla de zonas de entrega sin errores de vista.
     */
    public function testAdminCanAccessDeliveryZonesPage(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        config()->set('services.google_maps.api_key', 'test-key');

        $response = $this->actingAs($admin)->get(route('admin.zonas-entrega.index'));

        $response->assertOk();
        $response->assertSee('Zonas de entrega');
        $response->assertSee('Cobertura definida por colonia o barrio');
        $response->assertDontSee('delivery-zone-picker-map');
        $response->assertDontSee('create-zone-location-search');
    }

    /**
     * Mantiene disponibles para el mapa las zonas que quedan fuera de la pagina actual.
     */
    public function testDeliveryZoneMapSearchIncludesZonesOutsidePaginatedList(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        config()->set('services.google_maps.api_key', 'test-key');

        foreach (range(1, 25) as $index) {
            DeliveryZone::query()->create([
                'uuid' => (string) Str::uuid(),
                'nombre' => sprintf('Zona %02d', $index),
                'slug' => sprintf('zona-%02d', $index),
                'municipio' => 'Puerto Barrios',
                'costo_base' => 15,
                'activa' => true,
            ]);
        }

        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'ZZZ BANVI I',
            'slug' => 'stc-banvi-i',
            'municipio' => 'Puerto Barrios',
            'costo_base' => 20,
            'activa' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.zonas-entrega.index'));

        $response->assertOk();
        $response->assertSee('Buscar zona guardada');
        $response->assertSee('stc-banvi-i');
        $response->assertSee('No se encontró ninguna zona con ese nombre');
    }

    /**
     * Permite reutilizar una zona eliminada sin dejar nombres bloqueados invisibles.
     */
    public function testAdminCanRecreateDeletedDeliveryZone(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $deletedZone = DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Colonia El Inde',
            'slug' => 'colonia-el-inde',
            'municipio' => 'Puerto Barrios',
            'costo_base' => 12,
            'activa' => false,
        ]);
        $deletedZone->delete();

        $response = $this->actingAs($admin)->post(route('admin.zonas-entrega.store'), [
            'nombre' => 'Colonia El Inde',
            'slug' => 'colonia-el-inde',
            'descripcion' => 'Cobertura residencial actualizada.',
            'municipio' => 'Puerto Barrios',
            'costo_base' => 15,
            'tiempo_estimado_min' => 45,
            'capacidad_diaria' => 80,
            'activa' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('delivery_zones', 1);
        $this->assertDatabaseHas('delivery_zones', [
            'id' => $deletedZone->id,
            'nombre' => 'Colonia El Inde',
            'costo_base' => 15,
            'activa' => true,
            'deleted_at' => null,
        ]);
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
