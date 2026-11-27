<?php

namespace Tests\Feature\Vendedor;

use App\Models\Pedido;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PickupOrderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Vendor, 1: Pedido}
     */
    private function pedidoListo(string $tipoEntrega): array
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $pedido = Pedido::factory()->create([
            'vendor_id' => $vendor->id,
            'estado' => 'listo_para_entrega',
            'estado_pago' => 'pagado',
            'metodo_pago' => 'efectivo',
            'tipo_entrega' => $tipoEntrega,
        ]);

        return [$vendor, $pedido];
    }

    public function test_vendor_marks_pickup_order_as_delivered(): void
    {
        [$vendor, $pedido] = $this->pedidoListo('recoger');

        $this->actingAs($vendor->user)
            ->patch(route('vendedor.pedidos.estado', $pedido), ['estado' => 'entregado'])
            ->assertSessionHasNoErrors();

        $this->assertSame('entregado', $pedido->refresh()->estadoValor());
    }

    public function test_vendor_cannot_deliver_home_delivery_orders(): void
    {
        [$vendor, $pedido] = $this->pedidoListo('domicilio');

        $this->actingAs($vendor->user)
            ->patch(route('vendedor.pedidos.estado', $pedido), ['estado' => 'entregado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('listo_para_entrega', $pedido->refresh()->estadoValor());
    }
}
