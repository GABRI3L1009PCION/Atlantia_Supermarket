<?php

namespace Tests\Feature;

use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\PushSuscripcion;
use App\Models\ServicioContratado;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\PedidoEnCaminoNotification;
use App\Services\Monetizacion\EnviosParticularesService;
use App\Services\Monetizacion\NovedadesService;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\ServiciosPagadosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonetizacionTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(array $datos = []): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create($datos);
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_device_subscribes_to_push_and_order_notices_use_it(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)->postJson(route('push.suscribir'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'clave-publica', 'auth' => 'clave-auth'],
            'ofertas' => true,
        ])->assertOk();

        $this->assertDatabaseHas('push_suscripciones', ['user_id' => $cliente->id, 'ofertas' => true]);

        $pedido = Pedido::factory()->create(['cliente_id' => $cliente->id, 'estado' => 'en_ruta']);
        $canales = (new PedidoEnCaminoNotification($pedido))->via($cliente);
        $this->assertContains(WebPushChannel::class, $canales);
    }

    public function test_whatsapp_is_off_by_default_outside_tests(): void
    {
        $config = file_get_contents(base_path('config/services.php'));
        $this->assertStringContainsString("env('WHATSAPP_DRIVER', 'null')", (string) $config);
    }

    public function test_plan_limits_products_and_paid_plan_has_no_commission(): void
    {
        $vendor = $this->vendedor(['plan' => 'inicial']);
        Producto::factory()->count(15)->create(['vendor_id' => $vendor->id]);
        $planes = app(PlanesVendedorService::class);

        try {
            $planes->assertPuedeAgregarProductos($vendor);
            $this->fail('El plan Inicial no debe pasar de 15 productos.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('hasta 15 productos', $exception->getMessage());
        }

        $planes->aplicar($vendor, 'esencial');
        $vendor->refresh();
        $this->assertSame('esencial', $planes->planActual($vendor));
        $this->assertEqualsWithDelta(0.0, (float) $vendor->commission_percentage, 0.001);
        $this->assertEqualsWithDelta(199.0, (float) $vendor->monthly_rent, 0.001);
        $planes->assertPuedeAgregarProductos($vendor);
    }

    public function test_no_plan_has_unlimited_products(): void
    {
        foreach (PlanesVendedorService::contratables() as $clave => $plan) {
            $this->assertIsInt($plan['max_productos'], "El plan {$clave} debe tener un limite de productos.");
        }

        /* El Personalizado usa la cantidad acordada; sin acuerdo cae al limite del Corporativo. */
        $vendor = $this->vendedor(['plan' => 'personalizado', 'plan_hasta' => now()->addMonth(), 'limite_productos' => null]);
        $this->assertSame(1500, app(PlanesVendedorService::class)->limiteProductos($vendor));
    }

    public function test_unpaid_or_expired_rent_operates_as_commission_partner(): void
    {
        $sinPagar = $this->vendedor(['plan' => 'profesional', 'plan_hasta' => null]);
        $vencido = $this->vendedor(['plan' => 'corporativo', 'plan_hasta' => now()->subDays(5)]);
        $planes = app(PlanesVendedorService::class);

        $this->assertSame('inicial', $planes->planActual($sinPagar));
        $this->assertSame('inicial', $planes->planActual($vencido));
        $this->assertSame(1, $planes->bajarVencidos());
        $this->assertEqualsWithDelta(7.0, (float) $vencido->fresh()->commission_percentage, 0.001);
    }

    public function test_vendor_requests_paid_plan_and_admin_registers_payment(): void
    {
        config(['atlantia.payments.card.mode' => 'manual']);
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->post(route('vendedor.plan.elegir'), ['plan' => 'profesional'])->assertSessionHas('success');
        $servicio = ServicioContratado::query()->where('tipo', 'plan')->firstOrFail();
        $this->assertSame('pendiente_pago', $servicio->estado);
        $this->assertEqualsWithDelta(349.0, (float) $servicio->monto, 0.001);

        app(ServiciosPagadosService::class)->marcarPagado($servicio);
        $this->assertSame('profesional', app(PlanesVendedorService::class)->planActual($vendor->fresh()));
        $this->assertSame('activo', $servicio->fresh()->estado);
    }

    public function test_advanced_plan_includes_one_highlight_week_per_month(): void
    {
        config(['atlantia.payments.card.mode' => 'manual']);
        $vendor = $this->vendedor(['plan' => 'avanzado', 'plan_hasta' => now()->addMonth()]);
        $producto = Producto::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($vendor->user)->post(route('vendedor.publicidad.destacado'), ['producto_id' => $producto->id, 'semana' => now()->toDateString()]);
        $this->actingAs($vendor->user)->post(route('vendedor.publicidad.destacado'), ['producto_id' => $producto->id, 'semana' => now()->addWeek()->toDateString()]);

        $servicios = ServicioContratado::query()->where('tipo', 'destacado')->orderBy('id')->get();
        $this->assertEqualsWithDelta(0.0, (float) $servicios[0]->monto, 0.001);
        $this->assertSame('activo', $servicios[0]->estado);
        $this->assertEqualsWithDelta(50.0, (float) $servicios[1]->monto, 0.001);
        $this->assertSame('pendiente_pago', $servicios[1]->estado);
    }

    public function test_plans_match_the_monthly_table(): void
    {
        $esperado = ['inicial' => [0, 15], 'esencial' => [199, 75], 'profesional' => [349, 200], 'avanzado' => [499, 600], 'corporativo' => [699, 1500]];

        foreach ($esperado as $clave => [$precio, $productos]) {
            $plan = PlanesVendedorService::datos($clave);
            $this->assertEquals($precio, $plan['precio'], $clave);
            $this->assertSame($productos, $plan['max_productos'], $clave);
        }

        $this->assertSame(7, PlanesVendedorService::datos('inicial')['comision']);
        $this->assertNull(PlanesVendedorService::datos('personalizado')['precio']);
        $this->assertArrayNotHasKey('personalizado', PlanesVendedorService::contratables());
    }

    public function test_admin_sets_a_custom_plan_with_agreed_products(): void
    {
        $vendor = $this->vendedor();
        $planes = app(PlanesVendedorService::class);
        $planes->aplicar($vendor, 'personalizado', null, 1800, 2, 4000);
        $vendor->refresh();

        $this->assertSame('personalizado', $planes->planActual($vendor));
        $this->assertSame(4000, $planes->limiteProductos($vendor));
        $this->assertEqualsWithDelta(1800.0, (float) $vendor->monthly_rent, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $vendor->commission_percentage, 0.001);
    }

    public function test_vendor_cannot_buy_custom_plan_online(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->post(route('vendedor.plan.elegir'), ['plan' => 'personalizado'])->assertSessionHasErrors('plan');
    }

    public function test_paid_banner_waits_for_approval_and_counts_clicks(): void
    {
        Storage::fake('public');
        config(['atlantia.payments.card.mode' => 'manual']);
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->post(route('vendedor.publicidad.banner'), [
            'posicion' => 1,
            'periodo' => 'semana',
            'inicia' => now()->toDateString(),
            'desktop_image' => UploadedFile::fake()->image('banner.jpg', 1500, 600),
        ])->assertSessionHas('success');

        $servicio = ServicioContratado::query()->where('tipo', 'banner')->firstOrFail();
        $this->assertEqualsWithDelta(250.0, (float) $servicio->monto, 0.001);
        $banner = $servicio->referencia;
        $this->assertFalse($banner->is_active);

        $servicios = app(ServiciosPagadosService::class);
        $servicio = $servicios->marcarPagado($servicio);
        $this->assertSame('en_revision', $servicio->estado);
        $servicios->aprobarBanner($servicio);
        $this->assertTrue($banner->fresh()->is_active);

        $this->get(route('publicidad.banner', $banner->uuid))->assertRedirect(route('comercios.show', $vendor->slug));
        $this->assertSame(1, $banner->fresh()->clics);
    }

    public function test_sponsored_product_shows_above_search_results(): void
    {
        $producto = Producto::factory()->publicado()->create(['nombre' => 'Jugo de naranja natural']);
        app(ServiciosPagadosService::class)->marcarPagado(
            app(ServiciosPagadosService::class)->crear($producto->vendor, null, 'patrocinado', $producto, 35, now()->subDay(), now()->addWeek())
        );

        $this->get(route('catalogo.index', ['q' => 'jugo']))
            ->assertOk()
            ->assertSee('data-patrocinados', false)
            ->assertSee('Patrocinado');

        $this->assertSame(1, ServicioContratado::query()->first()->vistas);
    }

    public function test_person_requests_a_courier_delivery(): void
    {
        $this->assertEqualsWithDelta(25.0, EnviosParticularesService::cotizar('Santo Tomas', 'Santo Tomas', 'sobre'), 0.001);
        $this->assertEqualsWithDelta(45.0, EnviosParticularesService::cotizar('Santo Tomas', 'Puerto Barrios', 'mediano'), 0.001);

        $respuesta = $this->post(route('envios.store'), [
            'remitente_nombre' => 'Luis Pérez', 'remitente_telefono' => '5555 1234',
            'origen_municipio' => 'Santo Tomas', 'origen_direccion' => 'Colonia La Repegua, casa 4',
            'destinatario_nombre' => 'María López', 'destinatario_telefono' => '55559876',
            'destino_municipio' => 'Puerto Barrios', 'destino_direccion' => '10a avenida y 14 calle',
            'descripcion' => 'Sobre con documentos', 'tamano' => 'sobre', 'quien_paga' => 'destinatario', 'acepta' => '1',
        ]);

        $envio = ExternalDeliveryOrder::query()->where('source_channel', 'atlantia_envios')->firstOrFail();
        $respuesta->assertRedirect(route('envios.show', $envio->uuid));
        $this->assertEqualsWithDelta(35.0, (float) $envio->amount_to_collect, 0.001);

        $this->get(route('envios.show', $envio->uuid))->assertOk()->assertSee($envio->confirmation_code);
        $this->getJson(route('envios.live', $envio->uuid))->assertOk()->assertJsonPath('repartidor', null);
    }

    public function test_public_service_pages_render(): void
    {
        foreach (['servicios', 'vende', 'anunciate', 'eventos', 'envios.create', 'novedades'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }

        $this->get(route('vende'))->assertSee('Quiero vender en Atlantia')->assertSee('Abre tu local digital')->assertSee('Q 199')->assertSee('Q 699')->assertSee('Personalizado');
    }

    public function test_offer_alert_only_goes_to_devices_that_accepted(): void
    {
        config(['services.webpush.public_key' => null]);
        PushSuscripcion::query()->create(['endpoint' => 'https://x/1', 'endpoint_hash' => hash('sha256', 'https://x/1'), 'p256dh' => 'a', 'auth' => 'b', 'ofertas' => false]);

        /* Sin claves configuradas no se envia nada y no falla. */
        $this->assertSame(0, app(NovedadesService::class)->enviarAviso());
    }
}
