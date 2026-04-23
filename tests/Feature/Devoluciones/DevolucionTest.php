<?php

namespace Tests\Feature\Devoluciones;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\Cliente\Direccion;
use App\Models\Devolucion;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del flujo de devoluciones.
 */
class DevolucionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura roles base.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Cliente puede solicitar devolucion dentro de los 7 dias permitidos.
     */
    public function testClientePuedeSolicitarDevolucionDentroDeSieteDias(): void
    {
        [$cliente, $pedido] = $this->createDeliveredOrder(daysAgo: 3);

        $this->actingAs($cliente)
            ->post(route('cliente.devoluciones.store', $pedido), [
                'motivo' => 'incorrecto',
                'descripcion' => 'El producto recibido no coincide con lo que pedi en la compra.',
                'foto_evidencia' => UploadedFile::fake()->image('evidencia.jpg'),
            ])
            ->assertRedirect(route('cliente.pedidos.show', $pedido));

        $this->assertDatabaseHas('devoluciones', [
            'pedido_id' => $pedido->id,
            'user_id' => $cliente->id,
            'estado' => 'solicitada',
        ]);
    }

    /**
     * No permite devolucion despues de 7 dias.
     */
    public function testNoSePuedeSolicitarDevolucionDespuesDeSieteDias(): void
    {
        [$cliente, $pedido] = $this->createDeliveredOrder(daysAgo: 9);

        $this->actingAs($cliente)
            ->post(route('cliente.devoluciones.store', $pedido), [
                'motivo' => 'otro',
                'descripcion' => 'La compra ya no cumple mis necesidades y deseo devolverla.',
            ])
            ->assertForbidden();
    }

    /**
     * Administracion puede aprobar o rechazar devoluciones pendientes.
     */
    public function testAdminPuedeAprobarORechazarDevoluciones(): void
    {
        [$cliente, $pedido] = $this->createDeliveredOrder(daysAgo: 2);
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $devolucion = Devolucion::factory()->create([
            'pedido_id' => $pedido->id,
            'user_id' => $cliente->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.devoluciones.update', $devolucion), [
                'decision' => 'rechazada',
                'notas_admin' => 'No se encontraron evidencias suficientes para aprobar la devolucion.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('devoluciones', [
            'id' => $devolucion->id,
            'estado' => 'rechazada',
            'resuelta_por' => $admin->id,
        ]);
    }

    /**
     * Al aprobar una devolucion se restaura el stock del producto.
     */
    public function testAlAprobarUnaDevolucionSeRestauraElStock(): void
    {
        [$cliente, $pedido, $producto] = $this->createDeliveredOrder(daysAgo: 2, includeProduct: true);
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $devolucion = Devolucion::factory()->create([
            'pedido_id' => $pedido->id,
            'user_id' => $cliente->id,
        ]);

        $stockAntes = $producto->inventario->stock_actual;

        $this->actingAs($admin)
            ->patch(route('admin.devoluciones.update', $devolucion), [
                'decision' => 'aprobada',
                'monto_reembolso' => 25,
                'notas_admin' => 'Devolucion aprobada por producto incorrecto.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('devoluciones', [
            'id' => $devolucion->id,
            'estado' => 'aprobada',
        ]);
        $this->assertSame($stockAntes + 1, $producto->inventario()->firstOrFail()->stock_actual);
        $this->assertSame(EstadoPedido::Cancelado, $pedido->fresh()->estado);
    }

    /**
     * Crea pedido entregado apto para devolucion.
     *
     * @return array{0: User, 1: Pedido, 2?: Producto}
     */
    private function createDeliveredOrder(int $daysAgo, bool $includeProduct = false): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $vendorUser->id]);
        $producto = Producto::factory()->publicado()->create(['vendor_id' => $vendor->id, 'precio_base' => 25]);

        Inventario::factory()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 4,
            'stock_reservado' => 0,
        ]);

        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => $cliente->name,
            'telefono_contacto' => '+502 5512-3344',
            'municipio' => 'Puerto Barrios',
            'zona_o_barrio' => 'Centro',
            'direccion_linea_1' => '5a avenida 12-45',
            'referencia' => 'Frente a la tienda.',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
            'es_principal' => true,
            'activa' => true,
        ]);

        $pedido = Pedido::factory()->entregado()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'direccion_id' => $direccion->id,
            'subtotal' => 25,
            'envio' => 0,
            'impuestos' => 3,
            'descuento' => 0,
            'total' => 28,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'estado_pago' => EstadoPago::Pagado->value,
            'updated_at' => now()->subDays($daysAgo),
        ]);

        PedidoItem::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'producto_nombre_snapshot' => $producto->nombre,
            'producto_sku_snapshot' => $producto->sku,
            'cantidad' => 1,
            'precio_unitario_snapshot' => 25,
            'subtotal' => 25,
            'impuestos' => 3,
        ]);

        Payment::factory()->aprobado()->create([
            'pedido_id' => $pedido->id,
            'metodo' => MetodoPago::Efectivo->value,
            'monto' => 28,
        ]);

        return $includeProduct
            ? [$cliente, $pedido, $producto]
            : [$cliente, $pedido];
    }
}
