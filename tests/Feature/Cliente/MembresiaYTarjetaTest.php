<?php

namespace Tests\Feature\Cliente;

use App\Models\User;
use App\Services\Clientes\MembresiaService;
use App\Services\Pagos\Recurrente\RecurrentePagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MembresiaYTarjetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /* Estas pruebas cubren la membresia encendida; apagada se prueba aparte. */
        config(['atlantia.membresia.activa' => true]);
    }

    private function cliente(): User
    {
        Role::findOrCreate('cliente', 'web');
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        return $cliente;
    }

    private function recurrenteActivo(): void
    {
        config([
            'atlantia.payments.card.mode' => 'recurrente',
            'services.recurrente.secret_key' => 'sk_test_prueba',
            'services.recurrente.base_url' => 'https://app.recurrente.com/api',
        ]);
    }

    public function test_free_shipping_only_for_active_members_above_minimum(): void
    {
        $servicio = app(MembresiaService::class);
        $miembro = User::factory()->create(['membresia_hasta' => now()->addDays(10)]);
        $vencido = User::factory()->create(['membresia_hasta' => now()->subDay()]);

        $this->assertTrue($servicio->envioGratis($miembro, 150));
        $this->assertFalse($servicio->envioGratis($miembro, 50));
        $this->assertFalse($servicio->envioGratis($vencido, 150));
    }

    public function test_joining_membership_activates_after_paid_checkout(): void
    {
        $this->recurrenteActivo();
        $cliente = $this->cliente();
        Http::fake([
            'app.recurrente.com/api/checkouts' => Http::response(['id' => 'ch_1', 'checkout_url' => 'https://app.recurrente.com/checkout-session/ch_1'], 201),
            'app.recurrente.com/api/checkouts/ch_1' => Http::response([
                'id' => 'ch_1', 'status' => 'paid',
                'payment' => ['paymentable' => ['type' => 'Subscription', 'id' => 'su_123']],
            ]),
        ]);

        $this->actingAs($cliente)->post(route('cliente.membresia.suscribir'))
            ->assertRedirect('https://app.recurrente.com/checkout-session/ch_1');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://app.recurrente.com/api/checkouts'
            && $request['items'][0]['charge_type'] === 'recurring'
            && $request['items'][0]['billing_interval'] === 'month');

        $this->actingAs($cliente)->get(route('cliente.membresia.retorno'))->assertRedirect(route('cliente.membresia.index'));

        $cliente->refresh();
        $this->assertSame('su_123', $cliente->membresia_suscripcion_id);
        $this->assertTrue(app(MembresiaService::class)->activa($cliente));

        /* El cobro del mes siguiente (webhook) extiende la membresia. */
        $hasta = $cliente->membresia_hasta->copy();
        app(RecurrentePagoService::class)->procesarEvento([
            'event_type' => 'intent.succeeded',
            'id' => 'in_2',
            'payment' => ['paymentable' => ['type' => 'Subscription', 'id' => 'su_123']],
        ]);
        $this->assertTrue($cliente->fresh()->membresia_hasta->greaterThan($hasta->addDays(25)));
    }

    public function test_saving_a_card_stores_only_the_token(): void
    {
        $this->recurrenteActivo();
        $cliente = $this->cliente();
        Http::fake([
            'app.recurrente.com/api/customers' => Http::response(['id' => 'cus_9'], 201),
            'app.recurrente.com/api/checkouts' => Http::response(['id' => 'ch_s', 'checkout_url' => 'https://app.recurrente.com/checkout-session/ch_s'], 201),
            'app.recurrente.com/api/checkouts/ch_s' => Http::response([
                'id' => 'ch_s', 'status' => 'paid',
                'payment_method' => ['id' => 'pay_m_77', 'card' => ['last4' => '4242', 'network' => 'visa']],
            ]),
        ]);

        $this->actingAs($cliente)->post(route('cliente.tarjeta.guardar'))->assertRedirect('https://app.recurrente.com/checkout-session/ch_s');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://app.recurrente.com/api/checkouts' && $request['mode'] === 'setup' && $request['customer_id'] === 'cus_9');

        $this->actingAs($cliente)->get(route('cliente.tarjeta.retorno'))->assertSessionHas('success');

        $cliente->refresh();
        $this->assertSame('pay_m_77', $cliente->recurrente_metodo_pago_id);
        $this->assertSame('Visa •••• 4242', $cliente->tarjeta_descripcion);
    }

    public function test_membership_page_and_pwa_files(): void
    {
        $this->actingAs($this->cliente())->get(route('cliente.membresia.index'))->assertOk()->assertSee('Membresía Envío Gratis');
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('"display": "standalone"', (string) file_get_contents(public_path('site.webmanifest')));
    }

    public function test_membresia_apagada_no_se_ofrece_ni_se_puede_contratar(): void
    {
        config(['atlantia.membresia.activa' => false]);
        $this->recurrenteActivo();
        $cliente = $this->cliente();

        $this->actingAs($cliente)->get(route('cliente.membresia.index'))->assertOk()
            ->assertSee('Tarjeta guardada')->assertDontSee('Membresía Envío Gratis');
        $this->actingAs($cliente)->post(route('cliente.membresia.suscribir'))->assertNotFound();
        $this->get(route('servicios'))->assertOk()->assertDontSee('Membresía Envío Gratis');
        $this->assertFalse(app(MembresiaService::class)->envioGratis($cliente, 500));
    }
}
