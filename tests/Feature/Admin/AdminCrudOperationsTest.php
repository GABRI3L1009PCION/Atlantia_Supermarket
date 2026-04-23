<?php

namespace Tests\Feature\Admin;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de operaciones clave del panel administrativo.
 */
class AdminCrudOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Aprueba un vendedor y guarda sus condiciones operativas.
     */
    public function testAdminCanApproveVendorAndPersistCommercialConfiguration(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $vendor = Vendor::factory()->pending()->create();

        $response = $this->actingAs($admin)->patch(route('admin.vendedores.approve', $vendor->uuid), [
            'commission_percentage' => 7.50,
            'monthly_rent' => 250.00,
            'observaciones' => 'Aprobado tras validacion documental completa.',
            'fel_validado' => true,
            'acepta_cash' => true,
            'acepta_transfer' => true,
            'acepta_card' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'is_approved' => 1,
            'status' => 'approved',
            'commission_percentage' => 7.50,
            'monthly_rent' => 250.00,
            'accepts_card' => 0,
        ]);
    }

    /**
     * Crea un producto administrativo con inventario inicial.
     */
    public function testAdminCanCreateProductAndInventory(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $vendor = Vendor::factory()->approved()->create();
        $categoria = Categoria::query()->create([
            'nombre' => 'Abarrotes secos',
            'slug' => 'abarrotes-secos',
            'descripcion' => 'Categoria de prueba',
            'icon' => 'bag',
            'orden' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.productos.store'), [
            'vendor_id' => $vendor->id,
            'categoria_id' => $categoria->id,
            'sku' => 'ATL-PRUEBA-001',
            'nombre' => 'Harina de maiz Atlantia 2 lb',
            'descripcion' => 'Producto creado desde pruebas administrativas.',
            'precio_base' => '18.50',
            'precio_oferta' => '',
            'peso_gramos' => 907,
            'unidad_medida' => 'paquete',
            'stock_actual' => 40,
            'stock_minimo' => 5,
            'stock_maximo' => 80,
            'requiere_refrigeracion' => false,
            'is_active' => true,
            'visible_catalogo' => true,
        ]);

        $response->assertRedirect();

        $producto = Producto::query()->where('sku', 'ATL-PRUEBA-001')->firstOrFail();

        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $producto->id,
            'stock_actual' => 40,
            'stock_minimo' => 5,
            'stock_maximo' => 80,
        ]);
    }

    /**
     * Suspender un vendedor oculta su catalogo activo.
     */
    public function testSuspendingVendorDisablesVisibleProducts(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $vendor = Vendor::factory()->approved()->create();
        $producto = Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'visible_catalogo' => true,
        ]);

        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 12,
            'stock_reservado' => 0,
            'stock_minimo' => 2,
            'stock_maximo' => 20,
            'ultima_actualizacion' => now(),
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.vendedores.suspend', $vendor->uuid), [
            'motivo_suspension' => 'Suspension por incumplimiento operativo reiterado.',
            'tipo_suspension' => 'operativa',
            'notificar_vendedor' => true,
            'permitir_reactivacion' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'status' => 'suspended',
            'is_approved' => 0,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'is_active' => 0,
            'visible_catalogo' => 0,
        ]);
    }

    /**
     * Modera resenas por lote desde el panel administrativo.
     */
    public function testAdminCanModerateReviewsInBatch(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $vendor = Vendor::factory()->approved()->create();
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id]);
        $productoDos = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id]);
        $pedido = Pedido::factory()->entregado()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
        ]);

        $resenaUno = Resena::query()->create([
            'uuid' => (string) Str::uuid(),
            'producto_id' => $producto->id,
            'cliente_id' => $cliente->id,
            'pedido_id' => $pedido->id,
            'calificacion' => 4,
            'titulo' => 'Muy buen producto',
            'contenido' => 'Entrega puntual y producto en buen estado.',
            'imagenes_count' => 0,
            'aprobada' => false,
            'flagged_ml' => false,
        ]);

        $resenaDos = Resena::query()->create([
            'uuid' => (string) Str::uuid(),
            'producto_id' => $productoDos->id,
            'cliente_id' => $cliente->id,
            'pedido_id' => $pedido->id,
            'calificacion' => 5,
            'titulo' => 'Excelente experiencia',
            'contenido' => 'Muy recomendado para compras semanales.',
            'imagenes_count' => 0,
            'aprobada' => false,
            'flagged_ml' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.resenas.batch-moderate'), [
            'resenas' => [$resenaUno->uuid, $resenaDos->uuid],
            'accion' => 'aprobar',
            'notas' => 'Moderacion masiva por revision operativa.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('resenas', ['id' => $resenaUno->id, 'aprobada' => 1, 'flagged_ml' => 0]);
        $this->assertDatabaseHas('resenas', ['id' => $resenaDos->id, 'aprobada' => 1, 'flagged_ml' => 0]);
    }
}
