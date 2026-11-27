<?php

namespace Tests\Feature\Vendedor;

use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cada página del panel del vendedor debe abrir al entrar desde el menú (sin filtros ni parámetros).
 */
class PanelVendedorAbreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function paginas(): array
    {
        return collect([
            'vendedor.dashboard', 'vendedor.pedidos.index', 'vendedor.devoluciones.index', 'vendedor.productos.index', 'vendedor.productos.importar',
            'vendedor.inventario.index', 'vendedor.promociones.index', 'vendedor.cupones.index', 'vendedor.resenas.index', 'vendedor.apariencia.edit',
            'vendedor.zonas-entrega.index', 'vendedor.operacion.edit', 'vendedor.pagos.index', 'vendedor.comisiones.index',
            'vendedor.plan.index', 'vendedor.reportes.index', 'vendedor.perfil-fiscal.edit', 'vendedor.dte.index',
            'vendedor.predicciones.index', 'vendedor.reabasto.index', 'cuenta.seguridad', 'vendedor.soporte.index',
        ])->mapWithKeys(fn (string $ruta): array => [$ruta => [$ruta]])->all();
    }

    #[DataProvider('paginas')]
    public function test_page_opens_without_parameters(string $ruta): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id]);
        Inventario::factory()->create(['producto_id' => $producto->id]);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'pendiente']);

        $this->actingAs($vendor->user)->get(route($ruta))->assertOk();
    }
}
