<?php

namespace Tests\Feature\Policies;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Pruebas de autorizacion para productos.
 */
class ProductoPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura roles base.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Vendedor solo puede editar sus propios productos.
     */
    public function testVendedorSoloEditaSusPropiosProductos(): void
    {
        [$vendorUserA, $vendorA] = $this->createVendedor();
        [$vendorUserB] = $this->createVendedor();
        $producto = $this->createProducto($vendorA);

        $this->assertTrue(Gate::forUser($vendorUserA)->allows('update', $producto));
        $this->assertFalse(Gate::forUser($vendorUserB)->allows('update', $producto));
    }

    /**
     * Admin puede editar cualquier producto.
     */
    public function testAdminPuedeEditarCualquierProducto(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        [, $vendor] = $this->createVendedor();
        $producto = $this->createProducto($vendor);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $producto));
    }

    /**
     * Crea vendedor aprobado.
     *
     * @return array{0: User, 1: Vendor}
     */
    private function createVendedor(): array
    {
        $user = User::factory()->vendedor()->create();
        $user->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $vendor];
    }

    /**
     * Crea producto base para policy.
     */
    private function createProducto(Vendor $vendor): Producto
    {
        $categoria = Categoria::query()->first()
            ?? Categoria::query()->create([
                'nombre' => 'Abarrotes',
                'slug' => 'abarrotes',
                'descripcion' => 'Categoria de pruebas.',
                'icon' => 'shopping-bag',
                'orden' => 1,
                'is_active' => true,
            ]);

        return Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'categoria_id' => $categoria->id,
        ]);
    }
}
