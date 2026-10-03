<?php

namespace Tests\Feature\Repartidor;

use App\Enums\EstadoPedido;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Cliente\Direccion;
use App\Models\CourierWallet;
use App\Models\CourierWalletMovement;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Geolocalizacion\SeguimientoGpsService;
use App\Services\Pedidos\PedidoRepartidorService;
use App\Services\Repartidores\DashboardRepartidorService;
use App\Services\Repartidores\DeliveryOfferService;
use App\Services\Repartidores\ExternalDeliveryOrderService;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourierOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_repartidor_actualiza_disponibilidad_y_auto_aceptacion(): void
    {
        $repartidor = $this->createRepartidor();

        $this->actingAs($repartidor)
            ->patch(route('repartidor.estado.disponibilidad'), [
                'availability_status' => 'available',
                'service_scope' => 'external',
                'vehicle_type' => 'moto',
            ])
            ->assertRedirect();

        $this->actingAs($repartidor)
            ->patch(route('repartidor.estado.auto-aceptacion'), [
                'auto_accept_enabled' => '1',
                'auto_accept_max_distance_km' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $repartidor->id,
            'availability_status' => 'available',
            'service_scope' => 'external',
            'auto_accept_enabled' => true,
        ]);
    }

    public function test_aceptar_oferta_interna_marca_ruta_y_ajusta_pedido(): void
    {
        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::Confirmado->value);

        $offer = DeliveryOffer::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'delivery_route_id' => $route->id,
            'repartidor_id' => $repartidor->id,
            'source_type' => 'entrepreneurs',
            'status' => 'pending',
            'expires_at' => now()->addMinute(),
            'estimated_gain' => 24,
            'payment_method' => 'efectivo',
        ]);

        $beforeAcceptance = app(DashboardRepartidorService::class)->metrics($repartidor);
        $this->assertNull($beforeAcceptance['ruta_actual']);
        $this->assertTrue($beforeAcceptance['offers']->contains('id', $offer->id));

        $this->actingAs($repartidor)
            ->patch(route('repartidor.ofertas.accept', $offer))
            ->assertRedirect(route('repartidor.pedidos.show', $pedido));

        $this->assertDatabaseHas('delivery_offers', [
            'id' => $offer->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('delivery_routes', [
            'id' => $route->id,
            'estado' => 'asignada',
            'estimated_earning' => 24,
        ]);
        $this->assertNotNull($route->fresh()->confirmation_code);
        $this->assertSame(EstadoPedido::EnPreparacion->value, $pedido->fresh()->estadoValor());
        $this->assertSame($route->id, app(DashboardRepartidorService::class)->metrics($repartidor)['ruta_actual']?->id);
    }

    public function test_no_permite_entregar_antes_de_llegar_al_cliente(): void
    {
        $this->expectException(TransaccionFallidaException::class);

        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::EnRuta->value);
        $route->update([
            'estado' => 'iniciada',
            'aceptada_at' => now()->subMinutes(20),
            'iniciada_at' => now()->subMinutes(10),
            'picked_up_at' => now()->subMinutes(10),
        ]);

        app(PedidoRepartidorService::class)->deliver($pedido->fresh(), [], $repartidor);
    }

    public function test_gps_usa_uuid_y_no_cambia_el_estado_operativo_del_pedido(): void
    {
        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::EnPreparacion->value);
        $route->update(['aceptada_at' => now()]);

        $status = app(SeguimientoGpsService::class)->storeLocation($repartidor, [
            'pedido_uuid' => $pedido->uuid,
            'latitude' => 15.731,
            'longitude' => -88.594,
            'estado' => 'asignado',
        ]);

        $this->assertSame($pedido->id, $status->pedido_id);
        $this->assertSame('asignada', $route->fresh()->estado);
        $this->assertSame(15.731, (float) data_get($route->fresh()->ruta_real, '0.latitude'));
        $this->assertSame(-88.594, (float) data_get($route->fresh()->ruta_real, '0.longitude'));
    }

    public function test_entrega_interna_registra_billetera_y_efectivo(): void
    {
        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::EnRuta->value, [
            'metodo_pago' => 'efectivo',
            'total' => 125,
        ]);
        $route->update([
            'estado' => 'iniciada',
            'aceptada_at' => now()->subMinutes(30),
            'iniciada_at' => now()->subMinutes(15),
            'picked_up_at' => now()->subMinutes(15),
            'arrived_customer_at' => now(),
            'estimated_earning' => 28,
            'tip_amount' => 5,
            'cash_to_collect' => 125,
            'confirmation_code' => '7284',
        ]);

        app(PedidoRepartidorService::class)->deliver($pedido->fresh(), [
            'confirmation_code' => '7284',
            'notas' => 'Entregado',
        ], $repartidor);

        $this->assertSame(EstadoPedido::Entregado->value, $pedido->fresh()->estadoValor());
        $this->assertNotNull($route->fresh()->delivered_code_confirmed_at);
        $this->assertDatabaseHas('courier_wallet_movements', [
            'user_id' => $repartidor->id,
            'pedido_id' => $pedido->id,
            'type' => 'earning',
            'amount' => 28,
        ]);
        $this->assertDatabaseHas('courier_wallet_movements', [
            'user_id' => $repartidor->id,
            'pedido_id' => $pedido->id,
            'type' => 'cash_collected',
            'amount' => 125,
        ]);

        $wallet = CourierWallet::query()->where('user_id', $repartidor->id)->firstOrFail();
        $this->assertEquals(33.00, (float) $wallet->available_balance);
        $this->assertEquals(125.00, (float) $wallet->cash_balance);

        $route->refresh()->update(['completada_at' => now()->subHour()]);
        $payload = app(MobileRepartidorPayloadService::class)->dashboard(
            app(DashboardRepartidorService::class)->metrics($repartidor)
        );

        $this->assertSame($pedido->uuid, data_get($payload, 'recent_completed_order.order.id'));

        app(PedidoRepartidorService::class)->acknowledgeCompletion($pedido->fresh(), $repartidor);

        $payload = app(MobileRepartidorPayloadService::class)->dashboard(
            app(DashboardRepartidorService::class)->metrics($repartidor)
        );

        $this->assertNull($payload['recent_completed_order']);
        $this->assertNotNull($route->fresh()->completion_acknowledged_at);
    }

    public function test_entrega_interna_rechaza_codigo_incorrecto(): void
    {
        $this->expectException(TransaccionFallidaException::class);

        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::EnRuta->value);
        $route->update([
            'estado' => 'iniciada',
            'aceptada_at' => now()->subMinutes(30),
            'iniciada_at' => now()->subMinutes(15),
            'picked_up_at' => now()->subMinutes(15),
            'arrived_customer_at' => now(),
            'confirmation_code' => '7284',
        ]);

        app(PedidoRepartidorService::class)->deliver($pedido->fresh(), ['confirmation_code' => '1111'], $repartidor);
    }

    public function test_verifica_codigo_sin_cerrar_entrega(): void
    {
        $repartidor = $this->createRepartidor();
        [$pedido, $route] = $this->createPedidoAsignado($repartidor, EstadoPedido::EnRuta->value);
        $route->update([
            'estado' => 'iniciada',
            'aceptada_at' => now()->subMinutes(30),
            'iniciada_at' => now()->subMinutes(15),
            'picked_up_at' => now()->subMinutes(15),
            'arrived_customer_at' => now(),
            'confirmation_code' => '7284',
        ]);

        app(PedidoRepartidorService::class)->verifyDeliveryCode($pedido->fresh(), '7284', $repartidor);

        $this->assertSame(EstadoPedido::EnRuta->value, $pedido->fresh()->estadoValor());
        $this->assertNotNull($route->fresh()->delivered_code_confirmed_at);

        app(PedidoRepartidorService::class)->deliver($pedido->fresh(), [], $repartidor);

        $this->assertSame(EstadoPedido::Entregado->value, $pedido->fresh()->estadoValor());
    }

    public function test_flujo_completo_de_entrega_externa(): void
    {
        $repartidor = $this->createRepartidor();
        $externalService = app(ExternalDeliveryOrderService::class);
        $offerService = app(DeliveryOfferService::class);

        $order = $externalService->create([
            'source_channel' => 'shopify',
            'external_reference' => 'SHOP-1001',
            'store_name' => 'Tienda Online Norte',
            'pickup_address' => 'Centro comercial, local 8',
            'pickup_latitude' => 15.73090000,
            'pickup_longitude' => -88.59440000,
            'customer_name' => 'Cliente Externo',
            'customer_phone' => '+502 5512-1122',
            'delivery_address' => 'Barrio El Centro',
            'delivery_latitude' => 15.74090000,
            'delivery_longitude' => -88.58440000,
            'payment_method' => 'cash',
            'amount_to_collect' => 90,
            'amount_to_pay_store' => 20,
            'courier_earning' => 30,
            'tip_amount' => 4,
        ]);

        $offer = $offerService->createForExternal($order, $repartidor, ['estimated_gain' => 30]);
        $offerService->accept($offer, $repartidor);

        $gps = app(SeguimientoGpsService::class)->storeLocation($repartidor, [
            'external_order_uuid' => $order->uuid,
            'latitude' => 15.731,
            'longitude' => -88.594,
            'estado' => 'asignado',
        ]);

        $this->assertSame($order->id, $gps->external_delivery_order_id);
        $this->assertSame(15.731, (float) data_get($order->fresh()->real_path, '0.latitude'));

        $order = $externalService->arrivedPickup($order->fresh(), $repartidor);
        $order = $externalService->pickedUp($order, $repartidor);
        $order = $externalService->arrivedCustomer($order, $repartidor);
        $order = $externalService->verifyDeliveryCode($order, $repartidor, (string) $order->confirmation_code);
        $order = $externalService->deliver($order, $repartidor);

        $this->assertDatabaseHas('external_delivery_orders', [
            'id' => $order->id,
            'status' => 'delivered',
            'repartidor_id' => $repartidor->id,
        ]);
        $this->assertTrue(CourierWalletMovement::query()
            ->where('user_id', $repartidor->id)
            ->where('type', 'earning')
            ->where('amount', 30)
            ->exists());
        $this->assertTrue(CourierWalletMovement::query()
            ->where('user_id', $repartidor->id)
            ->where('type', 'cash_paid_pickup')
            ->where('amount', -20)
            ->exists());

        $externalService->acknowledgeCompletion($order->fresh(), $repartidor);
        $this->assertNotNull($order->fresh()->completion_acknowledged_at);
    }

    private function createRepartidor(): User
    {
        $user = User::factory()->repartidor()->create();
        $user->assignRole('repartidor');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $pedidoOverrides
     * @return array{0: Pedido, 1: DeliveryRoute}
     */
    private function createPedidoAsignado(User $repartidor, string $estado, array $pedidoOverrides = []): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Emprendedor Atlantia',
            'direccion_comercial' => 'Mercado municipal',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
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
            'referencia' => 'Frente al parque.',
            'latitude' => 15.74090000,
            'longitude' => -88.58440000,
            'es_principal' => true,
            'activa' => true,
        ]);
        $pedido = Pedido::factory()->create(array_merge([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'direccion_id' => $direccion->id,
            'estado' => $estado,
            'metodo_pago' => 'efectivo',
            'estado_pago' => 'pendiente',
            'envio' => 25,
        ], $pedidoOverrides));

        $route = DeliveryRoute::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'repartidor_id' => $repartidor->id,
            'source_type' => 'entrepreneurs',
            'pickup_name' => $vendor->business_name,
            'pickup_address' => $vendor->direccion_comercial,
            'pickup_latitude' => $vendor->latitude,
            'pickup_longitude' => $vendor->longitude,
            'ruta_planificada' => ['stops' => 1],
            'ruta_real' => null,
            'distancia_km' => 3.5,
            'tiempo_estimado_min' => 18,
            'tiempo_real_min' => null,
            'estado' => 'asignada',
            'asignada_at' => now(),
            'estimated_earning' => 20,
            'cash_to_collect' => $pedido->metodoPagoValor() === 'efectivo' ? (float) $pedido->total : 0,
            'payment_method' => $pedido->metodoPagoValor(),
        ]);

        return [$pedido, $route];
    }
}
