<?php

namespace Tests\Feature\Policies;

use App\Enums\EstadoPedido;
use App\Models\Cliente\Direccion;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de autorizacion para pedidos.
 */
class PedidoPolicyTest extends TestCase
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
     * Cliente solo puede ver sus propios pedidos.
     */
    public function testClienteSoloVeSusPropiosPedidos(): void
    {
        [$clienteA, $pedidoA] = $this->createPedidoParaCliente();
        [$clienteB] = $this->createPedidoParaCliente();

        $this->assertTrue(Gate::forUser($clienteA)->allows('view', $pedidoA));
        $this->assertFalse(Gate::forUser($clienteB)->allows('view', $pedidoA));
    }

    /**
     * Vendedor solo ve pedidos de su catalogo.
     */
    public function testVendedorSoloVePedidosConSusProductos(): void
    {
        [$cliente, $pedido, $vendorA, $vendorB] = $this->createPedidoParaDosVendedores();

        $this->assertTrue(Gate::forUser($vendorA->user)->allows('viewVendorOrder', $pedido));
        $this->assertFalse(Gate::forUser($vendorB->user)->allows('viewVendorOrder', $pedido));
    }

    /**
     * Repartidor solo actualiza estado de sus pedidos asignados.
     */
    public function testRepartidorSoloActualizaEstadoDeSusPedidosAsignados(): void
    {
        [$cliente, $pedido] = $this->createPedidoParaCliente();

        $repartidorA = User::factory()->repartidor()->create();
        $repartidorA->assignRole('repartidor');
        $repartidorB = User::factory()->repartidor()->create();
        $repartidorB->assignRole('repartidor');

        DeliveryRoute::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'repartidor_id' => $repartidorA->id,
            'ruta_planificada' => ['stops' => 1],
            'ruta_real' => null,
            'distancia_km' => 3.5,
            'tiempo_estimado_min' => 18,
            'tiempo_real_min' => null,
            'estado' => 'asignada',
            'asignada_at' => now(),
        ]);

        $this->assertTrue(Gate::forUser($repartidorA)->allows('updateDeliveryStatus', $pedido));
        $this->assertFalse(Gate::forUser($repartidorB)->allows('updateDeliveryStatus', $pedido));
    }

    /**
     * Crea pedido sencillo para cliente.
     *
     * @return array{0: User, 1: Pedido}
     */
    private function createPedidoParaCliente(): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $vendorUser->id]);

        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => $cliente->name,
            'telefono_contacto' => '+502 5512-3344',
            'municipio' => 'Puerto Barrios',
            'zona_o_barrio' => 'Centro',
            'direccion_linea_1' => '5a avenida 12-45',
            'referencia' => 'Frente al parque.',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
            'es_principal' => true,
            'activa' => true,
        ]);

        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'direccion_id' => $direccion->id,
            'estado' => EstadoPedido::Confirmado->value,
        ]);

        return [$cliente, $pedido];
    }

    /**
     * Crea pedido asociado a un vendedor especifico y otro ajeno.
     *
     * @return array{0: User, 1: Pedido, 2: Vendor, 3: Vendor}
     */
    private function createPedidoParaDosVendedores(): array
    {
        [$cliente] = $this->createPedidoParaCliente();

        $vendorUserA = User::factory()->vendedor()->create();
        $vendorUserA->assignRole('vendedor');
        $vendorA = Vendor::factory()->approved()->create(['user_id' => $vendorUserA->id]);

        $vendorUserB = User::factory()->vendedor()->create();
        $vendorUserB->assignRole('vendedor');
        $vendorB = Vendor::factory()->approved()->create(['user_id' => $vendorUserB->id]);

        $direccion = Direccion::query()->where('user_id', $cliente->id)->firstOrFail();
        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendorA->id,
            'direccion_id' => $direccion->id,
            'estado' => EstadoPedido::Confirmado->value,
        ]);

        return [$cliente, $pedido, $vendorA, $vendorB];
    }
}
