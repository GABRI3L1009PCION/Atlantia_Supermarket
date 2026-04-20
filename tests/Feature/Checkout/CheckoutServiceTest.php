<?php

namespace Tests\Feature\Checkout;

use App\Exceptions\TransaccionFallidaException;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Cliente\Direccion;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\User;
use App\Services\Pedidos\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del checkout con validacion server-side.
 */
class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recalcula totales desde el precio del servidor e ignora snapshots manipulados.
     */
    public function testCheckoutUsesServerSidePriceInsteadOfCartSnapshot(): void
    {
        $cliente = User::factory()->cliente()->create();
        $producto = Producto::factory()->publicado()->create(['precio_base' => 25.00, 'precio_oferta' => null]);
        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 15,
            'stock_reservado' => 0,
            'stock_minimo' => 3,
            'stock_maximo' => 30,
            'ultima_actualizacion' => now(),
        ]);
        $direccion = Direccion::query()->create([
            'uuid' => fake()->uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => 'Cliente Atlantia',
            'telefono_contacto' => '+502 7948-1200',
            'municipio' => 'Puerto Barrios',
            'zona_o_barrio' => 'Centro',
            'direccion_linea_1' => 'Avenida principal',
            'referencia' => 'Frente al parque',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
            'es_principal' => true,
            'activa' => true,
        ]);
        $carrito = Carrito::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'estado' => 'activo',
        ]);
        CarritoItem::query()->create([
            'carrito_id' => $carrito->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario_snapshot' => 1.00,
        ]);

        $pedido = app(CheckoutService::class)->checkout($cliente, [
            'direccion_id' => $direccion->id,
            'envio' => 10.00,
            'metodo_pago' => 'efectivo',
        ]);

        $this->assertSame('50.00', $pedido->subtotal);
        $this->assertSame('6.00', $pedido->impuestos);
        $this->assertSame('66.00', $pedido->total);
        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $producto->id,
            'stock_reservado' => 2,
        ]);
    }

    /**
     * Falla de forma controlada cuando el cliente no tiene carrito activo.
     */
    public function testCheckoutFailsWithoutActiveCart(): void
    {
        $cliente = User::factory()->cliente()->create();

        $this->expectException(TransaccionFallidaException::class);

        app(CheckoutService::class)->checkout($cliente, ['direccion_id' => 999, 'metodo_pago' => 'efectivo']);
    }
}
