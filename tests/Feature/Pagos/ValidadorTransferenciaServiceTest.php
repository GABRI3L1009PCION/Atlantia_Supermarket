<?php

namespace Tests\Feature\Pagos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Services\Pagos\ValidadorTransferenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de validacion manual de transferencias.
 */
class ValidadorTransferenciaServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Al rechazar una transferencia se libera stock y se cancela el pedido.
     */
    public function test_rejecting_transfer_releases_reserved_stock_and_cancels_order(): void
    {
        $empleado = User::factory()->empleado()->create();
        $producto = Producto::factory()->publicado()->create();
        $inventario = Inventario::factory()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 10,
            'stock_reservado' => 2,
            'stock_minimo' => 1,
        ]);
        $pedido = Pedido::factory()->create([
            'estado' => EstadoPedido::Confirmado->value,
            'metodo_pago' => MetodoPago::Transferencia->value,
            'estado_pago' => EstadoPago::Validando->value,
        ]);
        PedidoItem::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);
        $payment = Payment::factory()->create([
            'pedido_id' => $pedido->id,
            'metodo' => MetodoPago::Transferencia->value,
            'estado' => EstadoPago::Validando->value,
        ]);

        $result = app(ValidadorTransferenciaService::class)->validar($payment, [
            'estado' => EstadoPago::Rechazado->value,
            'notas' => 'Comprobante invalido.',
        ], $empleado);

        $this->assertSame(EstadoPago::Rechazado, $result->estado);
        $this->assertSame(EstadoPago::Rechazado, $pedido->refresh()->estado_pago);
        $this->assertSame(EstadoPedido::Cancelado, $pedido->estado);
        $this->assertSame(0, $inventario->refresh()->stock_reservado);
    }
}
