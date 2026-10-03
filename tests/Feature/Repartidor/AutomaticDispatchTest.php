<?php

namespace Tests\Feature\Repartidor;

use App\Enums\EstadoPedido;
use App\Exceptions\TransaccionFallidaException;
use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\Cliente\Direccion;
use App\Models\CourierProfile;
use App\Models\DeliveryOffer;
use App\Models\ExternalDeliveryOrder;
use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Repartidores\AutomaticDispatchService;
use App\Services\Repartidores\DeliveryOfferService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomaticDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config()->set('dispatch.enabled', true);
        config()->set('dispatch.offer_ttl_seconds', 90);
        config()->set('dispatch.gps_max_age_seconds', 180);
        config()->set('dispatch.gps_max_accuracy_meters', 100);
        config()->set('dispatch.max_pickup_distance_km', 20);
        config()->set('dispatch.max_active_deliveries', 1);
        config()->set('dispatch.retry_courier_after_minutes', 15);
    }

    public function test_asigna_la_oferta_al_repartidor_disponible_mas_conveniente(): void
    {
        $near = $this->createCourier(15.7310, -88.5944);
        $this->createCourier(15.7900, -88.5944);
        $pedido = $this->createEligiblePedido();

        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);

        $this->assertNotNull($offer);
        $this->assertSame($near->id, $offer->repartidor_id);
        $this->assertSame('pending', $offer->status);
        $this->assertSame('automatic', $offer->metadata['dispatch']['mode']);
        $this->assertNull($offer->route->repartidor_id);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $near->id,
            'type' => 'entrega.oferta',
        ]);
    }

    public function test_excluye_gps_antiguo_impreciso_y_repartidores_con_carga_maxima(): void
    {
        $stale = $this->createCourier(15.7310, -88.5944, gpsAt: now()->subMinutes(10));
        $imprecise = $this->createCourier(15.7311, -88.5944, accuracy: 500);
        $busy = $this->createCourier(15.7312, -88.5944);
        $eligible = $this->createCourier(15.7500, -88.5944);
        $this->createActiveExternalDelivery($busy);
        $pedido = $this->createEligiblePedido();

        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);

        $this->assertNotNull($offer);
        $this->assertSame($eligible->id, $offer->repartidor_id);
        $this->assertNotContains($offer->repartidor_id, [$stale->id, $imprecise->id, $busy->id]);
    }

    public function test_pondera_distancia_y_carga_para_elegir_candidato(): void
    {
        config()->set('dispatch.max_active_deliveries', 2);
        config()->set('dispatch.load_weight', 25);

        $loaded = $this->createCourier(15.7310, -88.5944);
        $idle = $this->createCourier(15.7400, -88.5944);
        $this->createActiveExternalDelivery($loaded);
        $pedido = $this->createEligiblePedido();

        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);

        $this->assertNotNull($offer);
        $this->assertSame($idle->id, $offer->repartidor_id);
        $this->assertSame(0, $offer->metadata['dispatch']['active_load']);
    }

    public function test_rechazar_una_oferta_la_reasigna_al_siguiente_repartidor(): void
    {
        $first = $this->createCourier(15.7310, -88.5944);
        $second = $this->createCourier(15.7500, -88.5944);
        $pedido = $this->createEligiblePedido();
        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);

        app(DeliveryOfferService::class)->reject($offer, $first, 'No disponible');

        $this->assertSame('rejected', $offer->fresh()->status);
        $this->assertDatabaseHas('delivery_offers', [
            'pedido_id' => $pedido->id,
            'repartidor_id' => $second->id,
            'status' => 'pending',
        ]);
    }

    public function test_rechazo_limpia_asignacion_previa_aunque_no_haya_reemplazo(): void
    {
        $courier = $this->createCourier(15.7310, -88.5944);
        $pedido = $this->createEligiblePedido();
        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);
        $offer->route->update([
            'repartidor_id' => $courier->id,
            'estado' => 'asignada',
            'asignada_at' => now(),
        ]);

        app(DeliveryOfferService::class)->reject($offer, $courier, 'No disponible');

        $this->assertNull($offer->route->fresh()->repartidor_id);
        $this->assertSame('pendiente', $offer->route->fresh()->estado);
        $this->assertNull($offer->route->fresh()->asignada_at);
    }

    public function test_vence_oferta_y_reasigna_el_pedido_automaticamente(): void
    {
        $first = $this->createCourier(15.7310, -88.5944);
        $second = $this->createCourier(15.7500, -88.5944);
        $pedido = $this->createEligiblePedido();
        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);
        $offer->update(['expires_at' => now()->subSecond()]);

        $result = app(AutomaticDispatchService::class)->run();

        $this->assertSame(1, $result['expired']);
        $this->assertSame('expired', $offer->fresh()->status);
        $this->assertDatabaseHas('delivery_offers', [
            'pedido_id' => $pedido->id,
            'repartidor_id' => $second->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('delivery_offers', [
            'pedido_id' => $pedido->id,
            'repartidor_id' => $first->id,
            'status' => 'pending',
        ]);
    }

    public function test_despacha_entrega_externa_y_solo_asigna_al_aceptar(): void
    {
        $courier = $this->createCourier(15.7310, -88.5944, scope: 'external');
        $order = $this->createExternalOrder();

        $offer = app(AutomaticDispatchService::class)->dispatchExternal($order);

        $this->assertNotNull($offer);
        $this->assertSame($courier->id, $offer->repartidor_id);
        $this->assertNull($order->fresh()->repartidor_id);
        $this->assertSame('offered', $order->fresh()->status);

        app(DeliveryOfferService::class)->accept($offer, $courier);

        $this->assertSame($courier->id, $order->fresh()->repartidor_id);
        $this->assertSame('accepted', $order->fresh()->status);
    }

    public function test_despacha_pedido_propio_de_atlantia_a_repartidor_interno(): void
    {
        $internal = $this->createCourier(15.7310, -88.5944, scope: 'internal');
        $this->createCourier(15.7311, -88.5944, scope: 'entrepreneurs');
        $pedido = $this->createEligiblePedido(withVendor: false);

        $offer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);

        $this->assertNotNull($offer);
        $this->assertSame($internal->id, $offer->repartidor_id);
        $this->assertSame('internal', $offer->source_type);
        $this->assertSame('internal', $offer->route->source_type);
        $this->assertSame('Atlantia Supermarket', $offer->route->pickup_name);
    }

    public function test_el_cambio_a_estado_elegible_encola_el_despacho(): void
    {
        Queue::fake();
        $pedido = $this->createEligiblePedido(withVendor: false);
        Pedido::withoutEvents(fn () => $pedido->update(['estado' => EstadoPedido::Pendiente->value]));

        $pedido->update(['estado' => EstadoPedido::Confirmado->value]);

        Queue::assertPushed(
            ProcesarDespachoAutomatico::class,
            fn (ProcesarDespachoAutomatico $job): bool => $job->pedidoId === $pedido->id
        );
    }

    public function test_dos_repartidores_no_pueden_aceptar_el_mismo_pedido(): void
    {
        $first = $this->createCourier(15.7310, -88.5944);
        $second = $this->createCourier(15.7500, -88.5944);
        $pedido = $this->createEligiblePedido();
        $firstOffer = app(AutomaticDispatchService::class)->dispatchPedido($pedido);
        $secondOffer = DeliveryOffer::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'delivery_route_id' => $firstOffer->delivery_route_id,
            'repartidor_id' => $second->id,
            'source_type' => 'entrepreneurs',
            'status' => 'pending',
            'expires_at' => now()->addMinute(),
            'estimated_gain' => 25,
        ]);

        app(DeliveryOfferService::class)->accept($firstOffer, $first);

        try {
            app(DeliveryOfferService::class)->accept($secondOffer, $second);
            $this->fail('Una segunda oferta no debe poder aceptar el mismo pedido.');
        } catch (TransaccionFallidaException) {
            $this->assertTrue(true);
        }

        $this->assertSame('accepted', $firstOffer->fresh()->status);
        $this->assertSame('cancelled', $secondOffer->fresh()->status);
        $this->assertSame($first->id, $firstOffer->route->fresh()->repartidor_id);
    }

    private function createCourier(
        float $latitude,
        float $longitude,
        ?\DateTimeInterface $gpsAt = null,
        float $accuracy = 10,
        string $scope = 'both'
    ): User {
        $user = User::factory()->repartidor()->create(['status' => 'active']);
        $user->assignRole('repartidor');
        CourierProfile::query()->create([
            'user_id' => $user->id,
            'availability_status' => 'available',
            'service_scope' => $scope,
            'vehicle_type' => 'moto',
            'rating' => 5,
            'completion_rate' => 100,
            'last_online_at' => now(),
        ]);
        MarketCourierStatus::query()->create([
            'repartidor_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp_gps' => $gpsAt ?? now(),
            'estado' => 'disponible',
            'accuracy_meters' => $accuracy,
        ]);

        return $user;
    }

    private function createEligiblePedido(bool $withVendor = true): Pedido
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Comercio Atlantia',
            'direccion_comercial' => 'Mercado municipal',
            'latitude' => 15.7309,
            'longitude' => -88.5944,
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
            'referencia' => 'Frente al parque',
            'latitude' => 15.7409,
            'longitude' => -88.5844,
            'es_principal' => true,
            'activa' => true,
        ]);

        return Pedido::withoutEvents(fn (): Pedido => Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $withVendor ? $vendor->id : null,
            'direccion_id' => $direccion->id,
            'estado' => EstadoPedido::Confirmado->value,
            'metodo_pago' => 'efectivo',
            'estado_pago' => 'pendiente',
            'envio' => 25,
        ]));
    }

    private function createActiveExternalDelivery(User $courier): ExternalDeliveryOrder
    {
        return $this->createExternalOrder([
            'external_reference' => 'ACTIVE-'.$courier->id,
            'status' => 'accepted',
            'repartidor_id' => $courier->id,
            'accepted_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createExternalOrder(array $overrides = []): ExternalDeliveryOrder
    {
        return ExternalDeliveryOrder::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'source_channel' => 'api',
            'external_reference' => (string) Str::uuid(),
            'store_name' => 'Tienda externa',
            'pickup_address' => 'Mercado municipal',
            'pickup_latitude' => 15.7309,
            'pickup_longitude' => -88.5944,
            'customer_name' => 'Cliente externo',
            'delivery_address' => 'Barrio El Centro',
            'delivery_latitude' => 15.7409,
            'delivery_longitude' => -88.5844,
            'payment_method' => 'cash',
            'courier_earning' => 30,
            'estimated_distance_km' => 2,
            'status' => 'requested',
            'requested_at' => now(),
        ], $overrides));
    }
}
