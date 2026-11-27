<?php

namespace Tests\Feature\Vendedor;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidosVendedorVistaTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_empty_list_shows_real_data_without_made_up_numbers(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.pedidos.index'))
            ->assertOk()
            ->assertSee('Comercial El Puerto')
            ->assertSee('Aún no tienes pedidos')
            ->assertSee('Aún sin datos')
            ->assertDontSee('28 min')
            ->assertDontSee('92%')
            ->assertDontSee('vs ayer')
            ->assertDontSee('Atlantia Supermarket')
            ->assertDontSee('Nuevas promociones disponibles');
    }

    public function test_tabs_count_and_filter_orders_and_show_next_step(): void
    {
        $vendor = $this->vendedor();
        $nuevo = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'pendiente', 'tipo_entrega' => 'domicilio']);
        $preparando = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'preparando', 'tipo_entrega' => 'recoger']);

        $this->actingAs($vendor->user)->get(route('vendedor.pedidos.index'))
            ->assertOk()
            ->assertSee('Confirmar pedido')
            ->assertSee('Listo para recoger')
            ->assertSee('data-pestana="por_confirmar"', false);

        $this->actingAs($vendor->user)->get(route('vendedor.pedidos.index', ['estado' => 'por_confirmar']))
            ->assertOk()
            ->assertSee('#'.$nuevo->numero_pedido)
            ->assertDontSee('#'.$preparando->numero_pedido);
    }

    public function test_order_detail_shows_products_customer_payment_and_next_step(): void
    {
        $vendor = $this->vendedor();
        $pedido = Pedido::factory()->create([
            'vendor_id' => $vendor->id, 'estado' => 'confirmado', 'tipo_entrega' => 'domicilio',
            'metodo_pago' => 'efectivo', 'estado_pago' => 'pendiente', 'subtotal' => 298, 'descuento_promociones' => 0,
            'notas' => 'Sin bolsa, por favor',
        ]);
        PedidoItem::query()->create([
            'pedido_id' => $pedido->id, 'producto_id' => Producto::factory()->create(['vendor_id' => $vendor->id])->id, 'producto_nombre_snapshot' => 'Máscara Sky High',
            'cantidad' => 2, 'precio_unitario_snapshot' => 149, 'subtotal' => 298, 'descuento' => 0, 'impuestos' => 0,
        ]);

        $this->actingAs($vendor->user)->get(route('vendedor.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('#'.$pedido->numero_pedido)
            ->assertSee('Productos para preparar')
            ->assertSee('Máscara Sky High')
            ->assertSee('Sin bolsa, por favor')
            ->assertSee('Q298.00')
            ->assertSee('Empezar a preparar')
            ->assertSee('El repartidor cobra en efectivo al entregar.')
            ->assertSee('data-avance', false)
            ->assertDontSee('Atlantia Supermarket');
    }

    public function test_vendor_cannot_skip_steps(): void
    {
        $vendor = $this->vendedor();
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'pendiente', 'tipo_entrega' => 'domicilio', 'estado_pago' => 'pagado', 'metodo_pago' => 'efectivo']);

        $this->actingAs($vendor->user)
            ->patch(route('vendedor.pedidos.estado', $pedido), ['estado' => 'listo_para_entrega'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('pendiente', $pedido->refresh()->estadoValor());

        $this->actingAs($vendor->user)
            ->patch(route('vendedor.pedidos.estado', $pedido), ['estado' => 'confirmado'])
            ->assertSessionHasNoErrors();

        $this->assertSame('confirmado', $pedido->refresh()->estadoValor());
    }
}
