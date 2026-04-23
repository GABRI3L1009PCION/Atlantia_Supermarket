<?php

namespace Tests\Feature\Security;

use App\Enums\EstadoPedido;
use App\Models\Cliente\Direccion;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de acceso por rol y ownership.
 */
class AuthorizationTest extends TestCase
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
     * Un cliente no puede entrar al panel admin.
     */
    public function testClienteNoPuedeAccederARutasDeAdmin(): void
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    /**
     * Un vendedor no puede ver pedidos de otra tienda.
     */
    public function testVendedorNoPuedeVerPedidosDeOtroVendedor(): void
    {
        [$userA, $vendorA] = $this->createVendedorAprobado();
        [$userB, $vendorB] = $this->createVendedorAprobado();
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        $direccion = $this->createDireccion($cliente);

        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendorB->id,
            'direccion_id' => $direccion->id,
            'estado' => EstadoPedido::Confirmado->value,
        ]);

        $this->actingAs($userA)
            ->get(route('vendedor.pedidos.show', $pedido))
            ->assertForbidden();
    }

    /**
     * Un repartidor solo puede ver sus entregas asignadas.
     */
    public function testRepartidorSoloVeSusPedidosAsignados(): void
    {
        $repartidorA = User::factory()->repartidor()->create();
        $repartidorA->assignRole('repartidor');
        $repartidorB = User::factory()->repartidor()->create();
        $repartidorB->assignRole('repartidor');

        [$cliente, $direccion, $pedido] = $this->createPedidoBase();

        DeliveryRoute::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'repartidor_id' => $repartidorB->id,
            'ruta_planificada' => ['stops' => 1],
            'ruta_real' => null,
            'distancia_km' => 2.4,
            'tiempo_estimado_min' => 15,
            'tiempo_real_min' => null,
            'estado' => 'asignada',
            'asignada_at' => now(),
        ]);

        $this->actingAs($repartidorA)
            ->get(route('repartidor.pedidos.show', $pedido))
            ->assertForbidden();
    }

    /**
     * Crea vendedor aprobado.
     *
     * @return array{0: User, 1: Vendor}
     */
    private function createVendedorAprobado(): array
    {
        $user = User::factory()->vendedor()->create();
        $user->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $vendor];
    }

    /**
     * Crea direccion valida.
     */
    private function createDireccion(User $cliente): Direccion
    {
        return Direccion::query()->create([
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
    }

    /**
     * Crea cliente, direccion y pedido base.
     *
     * @return array{0: User, 1: Direccion, 2: Pedido}
     */
    private function createPedidoBase(): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        [, $vendor] = $this->createVendedorAprobado();
        $direccion = $this->createDireccion($cliente);

        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'direccion_id' => $direccion->id,
            'estado' => EstadoPedido::Confirmado->value,
        ]);

        return [$cliente, $direccion, $pedido];
    }
}
