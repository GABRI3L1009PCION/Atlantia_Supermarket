<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryOffer;
use App\Models\ExternalDeliveryOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalDeliveryOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_crea_y_oferta_entrega_externa_a_repartidor(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        $repartidor = User::factory()->repartidor()->create();
        $repartidor->assignRole('repartidor');

        $this->actingAs($admin)
            ->post(route('admin.entregas-externas.store'), [
                'source_channel' => 'woocommerce',
                'external_reference' => 'WOO-1001',
                'store_name' => 'Tienda Online Atlantia',
                'pickup_address' => 'Plaza central, local 4',
                'pickup_latitude' => 15.73090000,
                'pickup_longitude' => -88.59440000,
                'customer_name' => 'Cliente Externo',
                'customer_phone' => '+502 5512-0000',
                'delivery_address' => 'Barrio El Centro',
                'delivery_latitude' => 15.74090000,
                'delivery_longitude' => -88.58440000,
                'payment_method' => 'cash',
                'amount_to_collect' => 150,
                'amount_to_pay_store' => 0,
                'tip_amount' => 5,
            ])
            ->assertRedirect();

        $order = ExternalDeliveryOrder::query()->firstOrFail();

        $this->assertSame('requested', $order->status);
        $this->assertGreaterThan(0, (float) $order->delivery_fee);
        $this->assertGreaterThan(0, (float) $order->courier_earning);

        $this->actingAs($admin)
            ->patch(route('admin.entregas-externas.assign', $order), [
                'repartidor_id' => $repartidor->id,
                'estimated_gain' => 35,
                'ttl_seconds' => 120,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('external_delivery_orders', [
            'id' => $order->id,
            'repartidor_id' => $repartidor->id,
            'status' => 'offered',
        ]);

        $this->assertTrue(DeliveryOffer::query()
            ->where('external_delivery_order_id', $order->id)
            ->where('repartidor_id', $repartidor->id)
            ->where('estimated_gain', 35)
            ->where('status', 'pending')
            ->exists());
    }
}
