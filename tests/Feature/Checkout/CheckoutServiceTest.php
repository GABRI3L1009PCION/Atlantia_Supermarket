<?php

namespace Tests\Feature\Checkout;

use App\Contracts\PasarelaPagoContract;
use App\DTOs\PagoResultado;
use App\DTOs\PedidoDTO;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Exceptions\DireccionFueraDeZonaException;
use App\Exceptions\PagoRechazadoException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cliente\Direccion;
use App\Models\DeliveryZone;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del flujo de checkout corregido.
 */
class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura dependencias base del test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Verifica que el stock ya este reservado cuando inicia el cobro.
     */
    public function testStockSeReservaAntesDeProcesarElPago(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 2);

        $stockReservadoAntesDelCobro = false;

        $this->fakePasarelaAprobada(function (Pedido $pedido) use ($producto, &$stockReservadoAntesDelCobro): void {
            $inventario = Inventario::query()->where('producto_id', $producto->id)->firstOrFail();
            $stockReservadoAntesDelCobro = $inventario->stock_reservado === 2;

            $this->assertSame(EstadoPedido::Pendiente, $pedido->estado);
        });

        $pedido = app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );

        $this->assertTrue($stockReservadoAntesDelCobro);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $producto->id,
            'stock_reservado' => 2,
        ]);
    }

    /**
     * Si el pago falla, el stock reservado debe liberarse.
     */
    public function testSiElPagoFallaElStockSeRestaura(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);

        $this->fakePasarelaRechazada();

        $this->expectException(PagoRechazadoException::class);

        try {
            app(\App\Services\Pedidos\CheckoutService::class)->checkout(
                $cliente,
                PedidoDTO::fromCheckoutArray([
                    'direccion_id' => $direccion->id,
                    'metodo_pago' => MetodoPago::Tarjeta->value,
                    'card_token' => 'pm_test_checkout',
                    'envio' => 10,
                ])
            );
        } finally {
            $this->assertDatabaseHas('inventarios', [
                'producto_id' => $producto->id,
                'stock_reservado' => 0,
            ]);

            $pedido = Pedido::query()->latest('id')->first();
            $this->assertNotNull($pedido);
            $this->assertSame(EstadoPedido::Cancelado, $pedido->estado);
        }
    }

    /**
     * No permite checkout cuando el stock disponible no alcanza.
     */
    public function testNoSePuedeHacerCheckoutConStockInsuficiente(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(1);
        $this->createCarritoActivo($cliente, $producto, 2);
        $this->fakePasarelaAprobada();

        $this->expectException(StockInsuficienteException::class);

        app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );
    }

    /**
     * No permite checkout si la direccion esta fuera de cobertura activa.
     */
    public function testCheckoutConDireccionFueraDeZonaRetornaError(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion('Livingston');
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(DireccionFueraDeZonaException::class);

        app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );
    }

    /**
     * Rechaza municipios futuros aunque tengan zona creada hasta activar la fase operativa.
     */
    public function testCheckoutRechazaMunicipioFueraDeFaseAunqueTengaZonaActiva(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion('Livingston');
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Livingston futuro',
            'slug' => 'livingston-futuro',
            'descripcion' => 'Zona futura aun no disponible para checkout.',
            'municipio' => 'Livingston',
            'costo_base' => 45,
            'latitude_centro' => 15.82830000,
            'longitude_centro' => -88.75060000,
            'activa' => true,
        ]);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(DireccionFueraDeZonaException::class);

        app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );
    }

    /**
     * Rechaza direcciones sin coordenadas GPS aunque el municipio este cubierto.
     */
    public function testCheckoutRechazaDireccionSinUbicacionExacta(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $direccion->update([
            'latitude' => null,
            'longitude' => null,
        ]);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(DireccionFueraDeZonaException::class);

        app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );
    }

    /**
     * Simula dos compras compitiendo por el ultimo item.
     */
    public function testSoloUnUsuarioLograComprarElUltimoItemDisponible(): void
    {
        [$clienteUno, $direccionUno] = $this->createClienteConDireccion();
        [$clienteDos, $direccionDos] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(1);

        $this->createCarritoActivo($clienteUno, $producto, 1);
        $this->createCarritoActivo($clienteDos, $producto, 1);
        $this->fakePasarelaAprobada();

        $pedidoExitoso = app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $clienteUno,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccionUno->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );

        $this->assertSame(EstadoPedido::Confirmado, $pedidoExitoso->fresh()->estado);

        $this->expectException(StockInsuficienteException::class);

        app(\App\Services\Pedidos\CheckoutService::class)->checkout(
            $clienteDos,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccionDos->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );
    }

    /**
     * Crea cliente con direccion activa.
     *
     * @return array{0: User, 1: Direccion}
     */
    private function createClienteConDireccion(string $municipio = 'Puerto Barrios'): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        if (in_array($municipio, ['Puerto Barrios', 'Santo Tomas'], true)) {
            DeliveryZone::query()->firstOrCreate(
                ['slug' => Str::slug($municipio)],
                [
                    'uuid' => (string) Str::uuid(),
                    'nombre' => $municipio,
                    'descripcion' => 'Zona activa para pruebas de checkout.',
                    'municipio' => $municipio,
                    'costo_base' => 15,
                    'latitude_centro' => 15.73090000,
                    'longitude_centro' => -88.59440000,
                    'activa' => true,
                ]
            );
        }

        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => $cliente->name,
            'telefono_contacto' => '+502 5512-3344',
            'municipio' => $municipio,
            'zona_o_barrio' => 'Centro',
            'direccion_linea_1' => '5a avenida 12-45',
            'direccion_linea_2' => null,
            'referencia' => 'Frente a la tienda del barrio.',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
            'mapbox_place_id' => null,
            'es_principal' => true,
            'activa' => true,
        ]);

        return [$cliente, $direccion];
    }

    /**
     * Crea producto publicado con inventario controlado.
     */
    private function createProductoConInventario(int $stock): Producto
    {
        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');

        $vendor = Vendor::factory()->approved()->create([
            'user_id' => $vendorUser->id,
        ]);

        $categoria = Categoria::query()->first()
            ?? Categoria::query()->create([
                'nombre' => 'Abarrotes',
                'slug' => 'abarrotes',
                'descripcion' => 'Categoria base de pruebas.',
                'icon' => 'shopping-bag',
                'orden' => 1,
                'is_active' => true,
            ]);

        $producto = Producto::factory()->publicado()->create([
            'vendor_id' => $vendor->id,
            'categoria_id' => $categoria->id,
            'precio_base' => 25,
            'precio_oferta' => null,
        ]);

        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => $stock,
            'stock_reservado' => 0,
            'stock_minimo' => 1,
            'stock_maximo' => 100,
            'ultima_actualizacion' => now(),
        ]);

        return $producto;
    }

    /**
     * Crea carrito activo con un item.
     */
    private function createCarritoActivo(User $cliente, Producto $producto, int $cantidad): void
    {
        $carrito = Carrito::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'session_id' => null,
            'estado' => 'activo',
            'expira_at' => now()->addDays(2),
        ]);

        CarritoItem::query()->create([
            'carrito_id' => $carrito->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario_snapshot' => $producto->precio_base,
        ]);
    }

    /**
     * Reemplaza la pasarela por una version aprobada de prueba.
     */
    private function fakePasarelaAprobada(?callable $antesDeCrearPago = null): void
    {
        $this->app->bind(PasarelaPagoContract::class, function () use ($antesDeCrearPago) {
            return new class ($antesDeCrearPago) implements PasarelaPagoContract {
                /**
                 * @param callable|null $antesDeCrearPago
                 */
                public function __construct(private $antesDeCrearPago)
                {
                }

                public function procesar(array $datos): PagoResultado
                {
                    return new PagoResultado(EstadoPago::Aprobado, 'txn_test', true, null, now());
                }

                public function registrarPagoCheckout(Pedido $pedido, PedidoDTO $pedidoDTO): Payment
                {
                    if (is_callable($this->antesDeCrearPago)) {
                        ($this->antesDeCrearPago)($pedido, $pedidoDTO);
                    }

                    $payment = Payment::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'pedido_id' => $pedido->id,
                        'metodo' => $pedidoDTO->metodoPago->value,
                        'monto' => $pedido->total,
                        'estado' => EstadoPago::Aprobado->value,
                        'transaccion_id_pasarela' => 'txn_test',
                        'hmac_validado' => true,
                        'referencia_bancaria' => null,
                        'validado_por' => null,
                        'validado_at' => now(),
                        'pasarela_payload' => ['gateway' => 'fake'],
                    ]);

                    $pedido->update(['estado_pago' => EstadoPago::Pagado->value]);

                    return $payment;
                }

                public function reembolsar(Payment $payment, float $monto): Payment
                {
                    $payment->update(['estado' => EstadoPago::Reembolsado->value]);

                    return $payment->refresh();
                }
            };
        });
    }

    /**
     * Reemplaza la pasarela por una version que rechaza el cobro.
     */
    private function fakePasarelaRechazada(): void
    {
        $this->app->bind(PasarelaPagoContract::class, function () {
            return new class () implements PasarelaPagoContract {
                public function procesar(array $datos): PagoResultado
                {
                    throw new PagoRechazadoException('La tarjeta fue rechazada por el banco emisor.');
                }

                public function registrarPagoCheckout(Pedido $pedido, PedidoDTO $pedidoDTO): Payment
                {
                    throw new PagoRechazadoException('La tarjeta fue rechazada por el banco emisor.');
                }

                public function reembolsar(Payment $payment, float $monto): Payment
                {
                    return $payment;
                }
            };
        });
    }
}
