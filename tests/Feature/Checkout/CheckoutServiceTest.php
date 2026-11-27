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
use App\Exceptions\TransaccionFallidaException;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cliente\Direccion;
use App\Models\Cupon;
use App\Models\DeliveryZone;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Clientes\DireccionService;
use App\Services\Clientes\SaldoService;
use App\Services\Pedidos\CheckoutService;
use App\Services\Pedidos\EstadoPedidoService;
use App\Services\Pedidos\PedidoVendedorService;
use App\Services\Promociones\CuponService;
use App\Services\Repartidores\AutomaticDispatchService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
    public function test_stock_se_reserva_antes_de_procesar_el_pago(): void
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

        $pedido = app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );

        $this->assertTrue($stockReservadoAntesDelCobro);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);

        /* El precio ya incluye IVA: el total no se recarga, solo se desglosa (Q 50 -> IVA Q 5.36). */
        $pedido->refresh();
        $this->assertEqualsWithDelta(50.00, (float) $pedido->subtotal, 0.001);
        $this->assertEqualsWithDelta(5.36, (float) $pedido->impuestos, 0.001);
        $this->assertEqualsWithDelta((float) $pedido->subtotal + (float) $pedido->envio, (float) $pedido->total, 0.001);
        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $producto->id,
            'stock_reservado' => 2,
        ]);
    }

    /**
     * Cuando la pasarela aprueba la tarjeta, los pedidos por vendedor quedan pagados
     * y la factura PDF se genera/envia automaticamente.
     */
    public function test_direccion_escrita_en_el_pago_solo_se_guarda_si_hay_compra(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $direccion->update(['borrador' => true, 'es_principal' => false]);

        /* Mientras no compre, no aparece en "Mis direcciones". */
        $this->assertCount(0, app(DireccionService::class)->forUser($cliente));

        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'envio' => 15,
        ]));

        $direccion->refresh();
        $this->assertFalse($direccion->borrador);
        $this->assertTrue($direccion->es_principal);
        $this->assertCount(1, app(DireccionService::class)->forUser($cliente));
    }

    public function test_pago_con_tarjeta_emite_factura_automaticamente(): void
    {
        Mail::fake();
        Storage::fake('local');

        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $pedido = app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Tarjeta->value,
                'envio' => 15,
                'facturacion_tipo' => 'cf',
                'razon_social' => 'Consumidor final',
                'nit_facturacion' => 'CF',
                'correo_facturacion' => 'cliente.factura@example.com',
            ])
        );

        $pedidoHijo = $pedido->pedidosHijos()->with('dte')->firstOrFail();

        $this->assertSame(EstadoPago::Pagado, $pedidoHijo->estado_pago);
        $this->assertNotNull($pedidoHijo->dte_id);
        $this->assertSame('certificado', $pedidoHijo->dte->estado);
        Storage::disk('local')->assertExists($pedidoHijo->dte->pdf_path);
        $this->assertDatabaseHas('sent_emails', [
            'to' => 'cliente.factura@example.com',
            'subject' => 'Factura Atlantia '.$pedidoHijo->dte->numero_dte,
            'status' => 'sent',
        ]);
    }

    /**
     * Si el pago falla, el stock reservado debe liberarse.
     */
    public function test_si_el_pago_falla_el_stock_se_restaura(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);

        $this->fakePasarelaRechazada();

        $this->expectException(PagoRechazadoException::class);

        try {
            app(CheckoutService::class)->checkout(
                $cliente,
                PedidoDTO::fromCheckoutArray([
                    'direccion_id' => $direccion->id,
                    'metodo_pago' => MetodoPago::Tarjeta->value,
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
     * Si el pago falla, el cupon no debe quedar consumido.
     */
    public function test_si_el_pago_falla_el_cupon_no_se_registra_como_usado(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);

        $cupon = Cupon::query()->create([
            'codigo' => 'FALLA10',
            'tipo' => 'monto_fijo',
            'valor' => 10,
            'minimo_compra' => 0,
            'maximo_descuento' => null,
            'usos_maximos' => null,
            'usos_actuales' => 0,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'activo' => true,
            'solo_primera_compra' => false,
            'descripcion' => 'Cupon de prueba para pago rechazado.',
        ]);

        $this->fakePasarelaRechazada();

        $this->expectException(PagoRechazadoException::class);

        try {
            app(CheckoutService::class)->checkout(
                $cliente,
                PedidoDTO::fromCheckoutArray([
                    'direccion_id' => $direccion->id,
                    'metodo_pago' => MetodoPago::Tarjeta->value,
                    'envio' => 10,
                    'coupon_code' => 'FALLA10',
                ])
            );
        } finally {
            $this->assertDatabaseMissing('cupon_uso', [
                'cupon_id' => $cupon->id,
                'user_id' => $cliente->id,
            ]);

            $this->assertDatabaseHas('cupones', [
                'id' => $cupon->id,
                'usos_actuales' => 0,
            ]);
        }
    }

    /**
     * No permite checkout cuando el stock disponible no alcanza.
     */
    public function test_no_se_puede_hacer_checkout_con_stock_insuficiente(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(1);
        $this->createCarritoActivo($cliente, $producto, 2);
        $this->fakePasarelaAprobada();

        $this->expectException(StockInsuficienteException::class);

        app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );
    }

    /**
     * Fuera de los municipios donde operamos no se puede pedir, aunque el cliente comparta su GPS
     * (antes pasaba y el envio salia en Q0).
     */
    public function test_checkout_rechaza_municipio_sin_cobertura_aunque_tenga_gps(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion('Livingston');
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(DireccionFueraDeZonaException::class);

        app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 0,
            ])
        );
    }

    /**
     * Un municipio nuevo se abre agregandolo a ATLANTIA_MARKETPLACE_MUNICIPIOS y creando su zona:
     * entonces se acepta y se cobra la tarifa de esa zona.
     */
    public function test_municipio_nuevo_se_habilita_con_la_configuracion_y_cobra_su_tarifa(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion('Livingston');
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Livingston centro',
            'slug' => 'livingston-centro',
            'descripcion' => 'Zona de Livingston.',
            'municipio' => 'Livingston',
            'costo_base' => 45,
            'latitude_centro' => 15.82830000,
            'longitude_centro' => -88.75060000,
            'activa' => true,
        ]);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();
        $dto = fn () => PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'envio' => 0,
        ]);

        /* Con la zona creada pero sin abrir el municipio, todavia no se acepta. */
        try {
            app(CheckoutService::class)->checkout($cliente, $dto());
            $this->fail('Livingston aun no es un municipio operativo.');
        } catch (DireccionFueraDeZonaException) {
        }

        config(['atlantia.marketplace.municipios' => ['Puerto Barrios', 'Santo Tomas', 'Livingston']]);
        $pedido = app(CheckoutService::class)->checkout($cliente, $dto());

        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
        $this->assertEquals(45, (float) $pedido->fresh()->envio);
    }

    /**
     * Acepta zonas operativas creadas sin mapa cuando la direccion tiene GPS exacto
     * y coincide con la colonia configurada.
     */
    public function test_checkout_acepta_zona_sin_mapa_por_colonia_con_gps_exacto(): void
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => $cliente->name,
            'telefono_contacto' => '+502 5512-3344',
            'municipio' => 'Santo Tomas',
            'zona_o_barrio' => 'Colonia Coviemport',
            'direccion_linea_1' => 'Interior de Coviemport',
            'direccion_linea_2' => null,
            'referencia' => 'Ubicacion GPS tomada en tiempo real.',
            'latitude' => 15.69060000,
            'longitude' => -88.62290000,
            'mapbox_place_id' => null,
            'es_principal' => true,
            'activa' => true,
        ]);
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Zona con poligono ajeno',
            'slug' => 'zona-poligono-ajeno',
            'descripcion' => 'Cobertura dibujada que no contiene Coviemport.',
            'municipio' => 'Santo Tomas',
            'costo_base' => 20,
            'latitude_centro' => 15.73090000,
            'longitude_centro' => -88.59440000,
            'poligono_geojson' => [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [-88.6000, 15.7200],
                            [-88.5900, 15.7200],
                            [-88.5900, 15.7300],
                            [-88.6000, 15.7300],
                            [-88.6000, 15.7200],
                        ]],
                    ],
                ]],
                'metadata' => ['barrios' => []],
            ],
            'activa' => true,
        ]);
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Colonia Coviemport',
            'slug' => 'stc-coviemport',
            'descripcion' => 'Zona operativa creada sin mapa para Coviemport.',
            'municipio' => 'Santo Tomas',
            'costo_base' => 15,
            'latitude_centro' => null,
            'longitude_centro' => null,
            'poligono_geojson' => [
                'type' => 'FeatureCollection',
                'features' => [],
                'metadata' => ['barrios' => ['Coviemport']],
            ],
            'activa' => true,
        ]);
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $pedido = app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );

        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
    }

    /**
     * Las colonias excluidas bloquean solo las entregas de productos propios de Atlantia.
     */
    public function test_colonia_excluida_bloquea_solo_productos_de_atlantia(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $direccion->update(['municipio' => 'Puerto Barrios', 'zona_o_barrio' => 'Barrio Los Tanques']);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        /* Comercio emprendedor: maneja sus propias zonas, no aplica la exclusion de Atlantia. */
        $pedido = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
        ]));
        $this->assertNotNull($pedido->id);

        /* Producto de Atlantia: se rechaza con un mensaje amable. */
        $producto->vendor->update(['slug' => 'atlantia-supermarket']);
        $this->createCarritoActivo($cliente, $producto->fresh(), 1);

        $this->expectException(DireccionFueraDeZonaException::class);
        $this->expectExceptionMessage('Por ahora Atlantia Store no entrega en Los Tanques');

        app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
        ]));
    }

    /**
     * Los productos de Atlantia no se recogen en tienda: Atlantia no tiene local propio.
     */
    public function test_productos_de_atlantia_no_permiten_recoger_en_tienda(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $producto->vendor->update(['slug' => 'atlantia-supermarket']);
        $this->createCarritoActivo($cliente, $producto->fresh(), 1);
        $this->fakePasarelaAprobada();

        $this->expectException(TransaccionFallidaException::class);
        $this->expectExceptionMessage('se entregan solo a domicilio');

        app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'tipo_entrega' => 'recoger',
        ]));
    }

    /**
     * La propina va al repartidor y el saldo a favor reduce lo que paga el cliente,
     * sin cambiar el total de la venta (factura) ni lo que corresponde al comercio.
     */
    public function test_propina_y_saldo_a_favor_ajustan_el_cobro_sin_cambiar_la_venta(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 2);
        $this->fakePasarelaAprobada();
        app(SaldoService::class)->acreditar($cliente, 20, 'ajuste_pedido', 'Producto faltante');

        $pedido = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'tipo_entrega' => 'domicilio',
            'propina' => 10,
            'usar_saldo' => true,
        ]))->refresh();

        $venta = (float) $pedido->total;
        $this->assertEqualsWithDelta(10.0, (float) $pedido->propina, 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $pedido->saldo_aplicado, 0.001);
        $this->assertEqualsWithDelta($venta + 10 - 20, $pedido->montoACobrar(), 0.001);
        $this->assertEqualsWithDelta($venta + 10 - 20, (float) $pedido->payments()->latest('id')->value('monto'), 0.001);
        $this->assertEqualsWithDelta(0.0, app(SaldoService::class)->disponible($cliente), 0.001);

        $hijo = $pedido->pedidosHijos()->firstOrFail();
        $this->assertEqualsWithDelta(10.0, (float) $hijo->propina, 0.001);
        $this->assertEqualsWithDelta($venta, (float) $hijo->total, 0.001);

        /* Si el pedido se cancela, el saldo vuelve al cliente. */
        app(EstadoPedidoService::class)->registrar($pedido, EstadoPedido::Cancelado, 'Prueba');
        $this->assertEqualsWithDelta(20.0, app(SaldoService::class)->disponible($cliente), 0.001);
    }

    /**
     * Fuera de horario solo se aceptan entregas programadas.
     */
    public function test_tienda_cerrada_solo_acepta_entregas_programadas(): void
    {
        $this->travelTo(now()->setTime(23, 30));
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $producto->vendor->update(['hora_apertura' => '07:00', 'hora_cierre' => '21:00']);
        $this->createCarritoActivo($cliente, $producto->fresh(), 1);
        $this->fakePasarelaAprobada();

        try {
            app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'tipo_entrega' => 'domicilio',
            ]));
            $this->fail('Debio rechazar la entrega inmediata con la tienda cerrada.');
        } catch (TransaccionFallidaException $exception) {
            $this->assertStringContainsString('abrimos mañana a las 7:00 a. m.', $exception->getMessage());
        }

        $pedido = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'tipo_entrega' => 'programado',
        ]));

        $this->assertSame('programado', $pedido->tipo_entrega);
    }

    /**
     * Una tienda en pausa no recibe pedidos nuevos.
     */
    public function test_tienda_en_pausa_no_recibe_pedidos(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $producto->vendor->update(['pausado_hasta' => now()->addHour()]);
        $this->createCarritoActivo($cliente, $producto->fresh(), 1);
        $this->fakePasarelaAprobada();

        $this->expectException(TransaccionFallidaException::class);
        $this->expectExceptionMessage('no está recibiendo pedidos');

        app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Efectivo->value,
            'tipo_entrega' => 'programado',
        ]));
    }

    /**
     * Recoger en tienda no cobra envio y queda marcado para que no se asigne repartidor.
     */
    public function test_recoger_en_tienda_no_cobra_envio_ni_se_despacha(): void
    {
        config(['dispatch.enabled' => true]);
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 2);
        $this->fakePasarelaAprobada();

        $pedido = app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'tipo_entrega' => 'recoger',
                'envio' => 15,
            ])
        )->refresh();

        $this->assertSame('recoger', $pedido->tipo_entrega);
        $this->assertEqualsWithDelta(0.0, (float) $pedido->envio, 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $pedido->total, 0.001);

        $hijo = $pedido->pedidosHijos()->firstOrFail();
        $this->assertSame('recoger', $hijo->tipo_entrega);
        $this->assertEqualsWithDelta(0.0, (float) $hijo->envio, 0.001);
        $this->assertNull(app(AutomaticDispatchService::class)->dispatchPedido($hijo));
    }

    /**
     * El GPS es opcional: sin coordenadas se usa la zona activa del municipio elegido.
     */
    public function test_checkout_acepta_direccion_sin_gps_en_municipio_con_zona(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $direccion->update([
            'latitude' => null,
            'longitude' => null,
        ]);
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => 'Puerto Barrios centro',
            'slug' => 'puerto-barrios-centro-sin-gps',
            'descripcion' => 'Zona base del municipio.',
            'municipio' => $direccion->municipio,
            'costo_base' => 15,
            'latitude_centro' => 15.72780000,
            'longitude_centro' => -88.59440000,
            'activa' => true,
        ]);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $pedido = app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );

        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
    }

    /**
     * Sin GPS y sin zona activa en el municipio, el pedido no puede continuar.
     */
    public function test_checkout_rechaza_direccion_sin_gps_ni_zona_activa(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion('Livingston');
        $direccion->update([
            'latitude' => null,
            'longitude' => null,
        ]);
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(DireccionFueraDeZonaException::class);

        app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
            ])
        );
    }

    /**
     * Rechaza solicitudes de cambio invalidas antes de crear el pedido.
     */
    public function test_checkout_rechaza_cambio_si_el_billete_no_supera_el_total(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(2);
        $this->createCarritoActivo($cliente, $producto, 1);
        $this->fakePasarelaAprobada();

        $this->expectException(TransaccionFallidaException::class);
        $this->expectExceptionMessage('El billete indicado para cambio debe ser mayor al total estimado.');

        app(CheckoutService::class)->checkout(
            $cliente,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccion->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 15,
                'solicita_cambio' => true,
                'cambio_para' => 30,
            ])
        );
    }

    /**
     * Simula dos compras compitiendo por el ultimo item.
     */
    public function test_solo_un_usuario_logra_comprar_el_ultimo_item_disponible(): void
    {
        [$clienteUno, $direccionUno] = $this->createClienteConDireccion();
        [$clienteDos, $direccionDos] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(1);

        $this->createCarritoActivo($clienteUno, $producto, 1);
        $this->createCarritoActivo($clienteDos, $producto, 1);
        $this->fakePasarelaAprobada();

        $pedidoExitoso = app(CheckoutService::class)->checkout(
            $clienteUno,
            PedidoDTO::fromCheckoutArray([
                'direccion_id' => $direccionUno->id,
                'metodo_pago' => MetodoPago::Efectivo->value,
                'envio' => 10,
            ])
        );

        $this->assertSame(EstadoPedido::Confirmado, $pedidoExitoso->fresh()->estado);

        $this->expectException(StockInsuficienteException::class);

        app(CheckoutService::class)->checkout(
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
     * Estilo DiDi: dos tiendas = dos entregas. La venta del vendedor no incluye el envio,
     * el pedido padre no se despacha y el seguimiento muestra una entrega por tienda.
     */
    public function test_pedido_de_dos_tiendas_se_entrega_por_separado(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $first = $this->createProductoConInventario(10);
        $second = $this->createProductoConInventario(10);
        $this->createCarritoActivo($cliente, $first, 1);
        $cart = Carrito::query()->where('user_id', $cliente->id)->firstOrFail();
        CarritoItem::query()->create(['carrito_id' => $cart->id, 'producto_id' => $second->id, 'cantidad' => 1, 'precio_unitario_snapshot' => $second->precio_base]);

        /* El carrito agrupa por tienda y avisa que llegan entregas separadas. */
        $this->actingAs($cliente)->get(route('cliente.carrito.index'))->assertOk()->assertSee('Tu pedido llegará en 2 entregas');

        $this->fakePasarelaAprobada();
        $order = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id, 'metodo_pago' => MetodoPago::Efectivo->value, 'envio' => 10,
        ]));

        foreach ($order->pedidosHijos as $hijo) {
            $this->assertGreaterThan(0, (float) $hijo->envio);
            $split = PaymentSplit::query()->where('vendor_id', $hijo->vendor_id)->first();
            if ($split !== null) {
                $this->assertEqualsWithDelta((float) $hijo->total - (float) $hijo->envio, (float) $split->monto_bruto, 0.001);
            }
        }

        config(['dispatch.enabled' => true]);
        $order->update(['estado' => EstadoPedido::Confirmado->value]);
        app(AutomaticDispatchService::class)->run();
        $this->assertDatabaseMissing('delivery_offers', ['pedido_id' => $order->id]);

        $this->actingAs($cliente)
            ->get(route('cliente.pedidos.seguimiento', $order))
            ->assertOk()
            ->assertSee('Tu pedido llega en 2 entregas')
            ->assertSee('Entrega 2', false);
    }

    /**
     * Crea producto publicado con inventario controlado.
     */
    public function test_products_from_two_stores_are_routed_only_to_their_respective_vendor(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $first = $this->createProductoConInventario(10);
        $second = $this->createProductoConInventario(10);
        $this->createCarritoActivo($cliente, $first, 1);
        $cart = Carrito::query()->where('user_id', $cliente->id)->firstOrFail();
        CarritoItem::query()->create(['carrito_id' => $cart->id, 'producto_id' => $second->id, 'cantidad' => 2, 'precio_unitario_snapshot' => $second->precio_base]);
        $this->fakePasarelaAprobada();
        $order = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id, 'metodo_pago' => MetodoPago::Efectivo->value, 'envio' => 10,
        ]));
        $children = $order->pedidosHijos()->with('items')->get()->keyBy('vendor_id');
        $this->assertCount(2, $children);
        foreach ([$first, $second] as $product) {
            $child = $children[$product->vendor_id];
            $this->assertSame([$product->id], $child->items->pluck('producto_id')->all());
            $received = app(PedidoVendedorService::class)->paginate($product->vendor->user);
            $this->assertSame([$child->id], $received->pluck('id')->all());
            $this->assertDatabaseHas('notifications', ['notifiable_id' => $product->vendor->user_id, 'notifiable_type' => User::class]);
        }
    }

    /**
     * El cupón de una tienda solo descuenta sus productos y solo lo paga ella.
     */
    public function test_cupon_de_una_tienda_solo_descuenta_y_cobra_a_esa_tienda(): void
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $deA = $this->createProductoConInventario(10);
        $deB = $this->createProductoConInventario(10);
        $this->createCarritoActivo($cliente, $deA, 1);
        $cart = Carrito::query()->where('user_id', $cliente->id)->firstOrFail();
        CarritoItem::query()->create(['carrito_id' => $cart->id, 'producto_id' => $deB->id, 'cantidad' => 1, 'precio_unitario_snapshot' => $deB->precio_base]);

        Cupon::query()->create([
            'uuid' => (string) Str::uuid(), 'vendor_id' => $deA->vendor_id, 'codigo' => 'TIENDAA20',
            'tipo' => 'porcentaje', 'valor' => 20, 'minimo_compra' => 0, 'activo' => true,
        ]);

        $this->fakePasarelaAprobada();
        $order = app(CheckoutService::class)->checkout($cliente, PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id, 'metodo_pago' => MetodoPago::Tarjeta->value, 'envio' => 10,
            'facturacion_tipo' => 'cf', 'razon_social' => 'Consumidor final', 'nit_facturacion' => 'CF',
            'coupon_code' => 'TIENDAA20',
        ]));

        $hijos = $order->pedidosHijos()->get()->keyBy('vendor_id');
        /* 20 % de Q25 (solo lo de la tienda A) = Q5; la tienda B no pierde nada. */
        $this->assertEqualsWithDelta(5.0, (float) $order->fresh()->descuento, 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $hijos[$deA->vendor_id]->descuento, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $hijos[$deB->vendor_id]->descuento, 0.001);

        $splitA = PaymentSplit::query()->where('vendor_id', $deA->vendor_id)->first();
        $splitB = PaymentSplit::query()->where('vendor_id', $deB->vendor_id)->first();
        $this->assertNotNull($splitA);
        $this->assertEqualsWithDelta(20.0, (float) $splitA->monto_bruto, 0.001);
        $this->assertEqualsWithDelta(25.0, (float) $splitB->monto_bruto, 0.001);
    }

    public function test_cupon_de_otra_tienda_no_aplica_si_no_llevas_sus_productos(): void
    {
        [$cliente] = $this->createClienteConDireccion();
        $deA = $this->createProductoConInventario(10);
        $deB = $this->createProductoConInventario(10);
        Cupon::query()->create([
            'uuid' => (string) Str::uuid(), 'vendor_id' => $deA->vendor_id, 'codigo' => 'SOLOA10',
            'tipo' => 'monto_fijo', 'valor' => 10, 'minimo_compra' => 0, 'activo' => true,
        ]);
        $this->createCarritoActivo($cliente, $deB, 2);
        $items = Carrito::query()->where('user_id', $cliente->id)->firstOrFail()->items()->with('producto')->get();

        $respuesta = app(CuponService::class)->resolver(
            $cliente, 'SOLOA10', 50.0, CheckoutService::subtotalesPorVendor($items)
        );

        $this->assertFalse($respuesta['valido']);
        $this->assertStringContainsString('tu carrito no tiene productos de esa tienda', $respuesta['mensaje']);
    }

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
            return new class($antesDeCrearPago) implements PasarelaPagoContract
            {
                /**
                 * @param  callable|null  $antesDeCrearPago
                 */
                public function __construct(private $antesDeCrearPago) {}

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
                        'monto' => $pedido->montoACobrar(),
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
            return new class implements PasarelaPagoContract
            {
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
