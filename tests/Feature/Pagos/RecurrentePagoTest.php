<?php

namespace Tests\Feature\Pagos;

use App\DTOs\PedidoDTO;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Exceptions\PagoRechazadoException;
use App\Http\Controllers\Cliente\CheckoutController;
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
use App\Services\Pagos\PasarelaPagoService;
use App\Services\Pagos\Recurrente\RecurrentePagoService;
use App\Services\Pagos\Recurrente\RecurrenteWebhookVerifier;
use App\Services\Pedidos\CheckoutService;
use App\Services\Pedidos\PedidoVendedorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Pago con tarjeta en linea mediante el checkout alojado de Recurrente.
 */
class RecurrentePagoTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    private const CHECKOUT_ID = 'ch_test_atlantia01';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
        Storage::fake('local');

        config([
            'atlantia.payments.card.mode' => 'recurrente',
            'services.recurrente.secret_key' => 'sk_test_atlantia',
            'services.recurrente.webhook_secret' => self::WEBHOOK_SECRET,
            'services.recurrente.base_url' => 'https://app.recurrente.com/api',
            'services.recurrente.currency' => 'GTQ',
            'services.recurrente.checkout_expires_minutes' => 30,
        ]);
    }

    public function test_checkout_con_tarjeta_crea_checkout_y_deja_el_pedido_pendiente(): void
    {
        $this->fakeCrearCheckout();
        [$pedido, $producto] = $this->checkoutConTarjeta();

        $payment = $pedido->payments()->firstOrFail();

        $this->assertSame(EstadoPago::Pendiente, $payment->estado);
        $this->assertSame(self::CHECKOUT_ID, $payment->transaccion_id_pasarela);
        $this->assertSame('recurrente', $payment->pasarela_payload['gateway']);
        $this->assertSame('https://app.recurrente.com/checkout-session/'.self::CHECKOUT_ID, $payment->pasarela_payload['checkout_url']);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
        $this->assertTrue(CheckoutService::esperaPagoEnLinea($payment));
        $this->assertSame(1, Inventario::query()->where('producto_id', $producto->id)->value('stock_reservado'));

        Http::assertSent(function (HttpRequest $request) use ($pedido, $payment): bool {
            return $request->url() === 'https://app.recurrente.com/api/checkouts'
                && $request->method() === 'POST'
                && $request->hasHeader('X-SECRET-KEY', 'sk_test_atlantia')
                && $request->hasHeader('Idempotency-Key', 'checkout-'.$payment->uuid)
                && $request['items'][0]['amount_in_cents'] === (int) round((float) $pedido->total * 100)
                && $request['items'][0]['currency'] === 'GTQ'
                && $request['metadata']['payment_uuid'] === $payment->uuid
                && str_contains($request['success_url'], '/cliente/pagos/recurrente/'.$payment->uuid.'/retorno');
        });
    }

    public function test_el_checkout_lleva_a_la_pagina_de_pago_de_atlantia(): void
    {
        $this->fakeCrearCheckout();
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);

        $url = $this->checkoutUrlTrasStore($cliente, $direccion);
        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame(route('cliente.pagos.recurrente.pagar', $payment), $url);
    }

    public function test_en_https_el_pago_se_muestra_embebido_dentro_de_atlantia(): void
    {
        $this->fakeCrearCheckout();
        [$pedido, , $cliente] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();

        $respuesta = $this->actingAs($cliente)
            ->get(str_replace('http://', 'https://', route('cliente.pagos.recurrente.pagar', $payment->uuid)))
            ->assertOk()
            ->assertSee('Paga tu pedido')
            ->assertSee('recurrente-checkout-container', false)
            ->assertSee('checkout-session\/'.self::CHECKOUT_ID, false);

        /* El iframe de Recurrente debe estar permitido por la politica de seguridad. */
        $this->assertMatchesRegularExpression('#frame-src [^;]*https://app\.recurrente\.com#', (string) $respuesta->headers->get('Content-Security-Policy'));
    }

    public function test_error_de_recurrente_rechaza_el_pago_y_libera_stock(): void
    {
        Http::fake(['https://app.recurrente.com/api/checkouts' => Http::response(['message' => 'error'], 500)]);
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);

        try {
            app(CheckoutService::class)->checkout($cliente, $this->dtoTarjeta($direccion));
            $this->fail('Se esperaba PagoRechazadoException.');
        } catch (PagoRechazadoException) {
            // esperado
        }

        $this->assertSame(0, Inventario::query()->where('producto_id', $producto->id)->value('stock_reservado'));
    }

    public function test_webhook_intent_succeeded_acredita_el_pago_y_confirma_el_pedido(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();

        $this->postWebhook($this->eventoExitoso($payment))->assertOk();

        $payment->refresh();
        $this->assertSame(EstadoPago::Aprobado, $payment->estado);
        $this->assertSame('in_test_001', $payment->pasarela_payload['recurrente_intent_id']);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
        $this->assertSame(EstadoPago::Pagado, $pedido->pedidosHijos()->firstOrFail()->estado_pago);
    }

    public function test_vendedor_no_puede_avanzar_un_pedido_sin_pago_acreditado(): void
    {
        $this->fakeCrearCheckout();
        [$pedido, $producto] = $this->checkoutConTarjeta();
        $pedidoHijo = $pedido->pedidosHijos()->firstOrFail();
        $servicio = app(PedidoVendedorService::class);

        try {
            $servicio->updateEstado($pedidoHijo, ['estado' => EstadoPedido::EnPreparacion->value], $producto->vendor->user);
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException) {
            $this->assertNotSame(EstadoPedido::EnPreparacion, $pedidoHijo->fresh()->estado);
        }

        $this->postWebhook($this->eventoExitoso($pedido->payments()->firstOrFail()))->assertOk();

        $actualizado = $servicio->updateEstado($pedidoHijo->fresh(), ['estado' => EstadoPedido::EnPreparacion->value], $producto->vendor->user);
        $this->assertSame(EstadoPedido::EnPreparacion, $actualizado->estado);
    }

    public function test_webhook_repetido_no_duplica_efectos(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $evento = $this->eventoExitoso($payment);

        $this->postWebhook($evento, 'msg_repetido')->assertOk();
        $this->postWebhook($evento, 'msg_repetido')->assertOk()->assertJson(['message' => 'Webhook ya procesado.']);

        $confirmaciones = $pedido->fresh()->historialEstados()->where('estado_nuevo', EstadoPedido::Confirmado->value)->count();
        $this->assertSame(1, $confirmaciones);
    }

    public function test_webhook_con_firma_invalida_se_rechaza(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $body = json_encode($this->eventoExitoso($payment), JSON_THROW_ON_ERROR);

        $this->call('POST', '/webhooks/recurrente', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SVIX_ID' => 'msg_falso',
            'HTTP_SVIX_TIMESTAMP' => (string) time(),
            'HTTP_SVIX_SIGNATURE' => 'v1,'.base64_encode(str_repeat('x', 32)),
        ], $body)->assertUnauthorized();

        $this->assertSame(EstadoPago::Pendiente, $payment->fresh()->estado);
    }

    public function test_monto_distinto_queda_en_revision_manual(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $evento = $this->eventoExitoso($payment);
        $evento['amount_in_cents'] = 100;

        $this->postWebhook($evento)->assertOk();

        $payment->refresh();
        $this->assertSame(EstadoPago::Validando, $payment->estado);
        $this->assertArrayHasKey('revision_manual', $payment->pasarela_payload);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
    }

    public function test_intento_fallido_no_cancela_el_pedido(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $evento = $this->eventoExitoso($payment);
        $evento['event_type'] = 'intent.failed';
        $evento['status'] = 'failed';
        $evento['details'] = ['failure_reason' => 'card_declined'];

        $this->postWebhook($evento)->assertOk();

        $payment->refresh();
        $this->assertSame(EstadoPago::Pendiente, $payment->estado);
        $this->assertSame('card_declined', $payment->pasarela_payload['recurrente_ultimo_fallo']['motivo']);
        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
    }

    public function test_checkout_vencido_cancela_pedido_y_libera_stock(): void
    {
        $this->fakeCrearCheckout(['status' => 'expired']);
        [$pedido, $producto] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $payment->forceFill(['created_at' => now()->subHour()])->save();

        $resumen = app(RecurrentePagoService::class)->reconciliarPendientes();

        $this->assertSame(1, $resumen['vencidos']);
        $this->assertSame(EstadoPago::Rechazado, $payment->fresh()->estado);
        $this->assertSame(EstadoPedido::Cancelado, $pedido->fresh()->estado);
        $this->assertSame(0, Inventario::query()->where('producto_id', $producto->id)->value('stock_reservado'));
    }

    public function test_retorno_verifica_en_la_api_y_acredita_pago_sin_webhook(): void
    {
        $this->fakeCrearCheckout(['status' => 'paid', 'latest_intent' => ['id' => 'in_test_api']]);
        [$pedido, , $cliente] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();

        $this->actingAs($cliente)
            ->get(route('cliente.pagos.recurrente.retorno', $payment->uuid))
            ->assertRedirect(route('cliente.pedidos.show', $pedido));

        $this->assertSame(EstadoPago::Aprobado, $payment->fresh()->estado);
        $this->assertSame('in_test_api', $payment->fresh()->pasarela_payload['recurrente_intent_id']);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado);
    }

    public function test_pagar_solo_permite_al_dueno_del_pedido(): void
    {
        $this->fakeCrearCheckout();
        [$pedido, , $cliente] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $otro = User::factory()->cliente()->create();

        $this->actingAs($otro)->get(route('cliente.pagos.recurrente.pagar', $payment->uuid))->assertForbidden();

        /* Sin embebido (no depende de si APP_URL es localhost o https): el dueño va directo al checkout. */
        config(['services.recurrente.embebido' => false]);
        $this->actingAs($cliente)
            ->get(route('cliente.pagos.recurrente.pagar', $payment->uuid))
            ->assertRedirect('https://app.recurrente.com/checkout-session/'.self::CHECKOUT_ID);
    }

    public function test_reembolso_usa_la_api_de_recurrente(): void
    {
        $this->fakeCrearCheckout();
        [$pedido] = $this->checkoutConTarjeta();
        $payment = $pedido->payments()->firstOrFail();
        $this->postWebhook($this->eventoExitoso($payment))->assertOk();
        $payment->refresh();

        app(PasarelaPagoService::class)->reembolsar($payment, 10.0);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://app.recurrente.com/api/refunds'
            && $request['intent_id'] === 'in_test_001'
            && $request['amount_in_cents'] === 1000
            && $request->hasHeader('Idempotency-Key', 'refund-'.$payment->uuid));
        $this->assertSame(EstadoPago::Reembolsado, $payment->fresh()->estado);
        $this->assertSame('re_test_001', $payment->fresh()->pasarela_payload['recurrente_refund']['id']);
    }

    public function test_verificador_svix_rechaza_timestamps_viejos_y_acepta_firmas_validas(): void
    {
        $verifier = new RecurrenteWebhookVerifier;
        $body = '{"event_type":"intent.succeeded"}';
        $now = 1_780_000_000;
        $firma = $this->firmar('msg_1', (string) $now, $body);

        $this->assertTrue($verifier->verify($body, 'msg_1', (string) $now, 'v1,otra '.$firma, self::WEBHOOK_SECRET, $now));
        $this->assertFalse($verifier->verify($body, 'msg_1', (string) $now, $firma, self::WEBHOOK_SECRET, $now + 301));
        $this->assertFalse($verifier->verify($body.' ', 'msg_1', (string) $now, $firma, self::WEBHOOK_SECRET, $now));
    }

    public function test_csp_permite_redirigir_al_checkout_de_recurrente(): void
    {
        $csp = (string) $this->get(route('home'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://app.recurrente.com', $csp);
        $this->assertStringNotContainsString('stripe', $csp);
    }

    /**
     * @param  array<string, mixed>  $estadoCheckout
     */
    private function fakeCrearCheckout(array $estadoCheckout = ['status' => 'unpaid']): void
    {
        Http::fake([
            'https://app.recurrente.com/api/checkouts' => Http::response([
                'id' => self::CHECKOUT_ID,
                'status' => 'unpaid',
                'checkout_url' => 'https://app.recurrente.com/checkout-session/'.self::CHECKOUT_ID,
                'live_mode' => false,
            ], 201),
            'https://app.recurrente.com/api/checkouts/*' => fn () => Http::response([
                'id' => self::CHECKOUT_ID,
                'currency' => 'GTQ',
                'total_in_cents' => (int) (Payment::query()->latest('id')->first()?->pasarela_payload['amount_in_cents'] ?? 0),
                ...$estadoCheckout,
            ]),
            'https://app.recurrente.com/api/refunds' => Http::response([
                'id' => 're_test_001',
                'status' => 'succeeded',
                'customer_refunded_amount_in_cents' => 1000,
            ]),
        ]);
    }

    /**
     * @return array{0: Pedido, 1: Producto, 2: User}
     */
    private function checkoutConTarjeta(): array
    {
        [$cliente, $direccion] = $this->createClienteConDireccion();
        $producto = $this->createProductoConInventario(3);
        $this->createCarritoActivo($cliente, $producto, 1);

        $pedido = app(CheckoutService::class)->checkout($cliente, $this->dtoTarjeta($direccion));

        return [$pedido, $producto, $cliente];
    }

    private function dtoTarjeta(Direccion $direccion): PedidoDTO
    {
        return PedidoDTO::fromCheckoutArray([
            'direccion_id' => $direccion->id,
            'metodo_pago' => MetodoPago::Tarjeta->value,
            'envio' => 15,
            'facturacion_tipo' => 'cf',
            'razon_social' => 'Consumidor final',
            'nit_facturacion' => 'CF',
            'correo_facturacion' => 'cliente.factura@example.com',
        ]);
    }

    private function checkoutUrlTrasStore(User $cliente, Direccion $direccion): ?string
    {
        $pedido = app(CheckoutService::class)->checkout($cliente, $this->dtoTarjeta($direccion));
        $method = new \ReflectionMethod(CheckoutController::class, 'checkoutUrlPendiente');

        return $method->invoke(app(CheckoutController::class), $pedido);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventoExitoso(Payment $payment): array
    {
        return [
            'type' => 'payment',
            'event_type' => 'intent.succeeded',
            'id' => 'in_test_001',
            'status' => 'succeeded',
            'amount_in_cents' => (int) $payment->pasarela_payload['amount_in_cents'],
            'currency' => 'GTQ',
            'checkout' => ['id' => self::CHECKOUT_ID, 'status' => 'paid'],
            'payment' => ['id' => 'pa_test_001'],
            'details' => ['failure_reason' => null],
        ];
    }

    /**
     * @param  array<string, mixed>  $evento
     */
    private function postWebhook(array $evento, ?string $id = null): TestResponse
    {
        $id ??= 'msg_'.Str::random(12);
        $timestamp = (string) time();
        $body = json_encode($evento, JSON_THROW_ON_ERROR);

        return $this->call('POST', '/webhooks/recurrente', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SVIX_ID' => $id,
            'HTTP_SVIX_TIMESTAMP' => $timestamp,
            'HTTP_SVIX_SIGNATURE' => $this->firmar($id, $timestamp, $body),
        ], $body);
    }

    private function firmar(string $id, string $timestamp, string $body): string
    {
        $key = base64_decode(substr(self::WEBHOOK_SECRET, 6));

        return 'v1,'.base64_encode(hash_hmac('sha256', $id.'.'.$timestamp.'.'.$body, $key, true));
    }

    /**
     * @return array{0: User, 1: Direccion}
     */
    private function createClienteConDireccion(): array
    {
        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');

        DeliveryZone::query()->firstOrCreate(
            ['slug' => 'puerto-barrios'],
            [
                'uuid' => (string) Str::uuid(),
                'nombre' => 'Puerto Barrios',
                'descripcion' => 'Zona activa para pruebas de pago.',
                'municipio' => 'Puerto Barrios',
                'costo_base' => 15,
                'latitude_centro' => 15.73090000,
                'longitude_centro' => -88.59440000,
                'activa' => true,
            ]
        );

        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => 'Casa',
            'nombre_contacto' => $cliente->name,
            'telefono_contacto' => '+502 5512-3344',
            'municipio' => 'Puerto Barrios',
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

    private function createProductoConInventario(int $stock): Producto
    {
        $vendorUser = User::factory()->vendedor()->create();
        $vendorUser->assignRole('vendedor');
        $vendor = Vendor::factory()->approved()->create(['user_id' => $vendorUser->id]);
        $categoria = Categoria::query()->first() ?? Categoria::query()->create([
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
}
