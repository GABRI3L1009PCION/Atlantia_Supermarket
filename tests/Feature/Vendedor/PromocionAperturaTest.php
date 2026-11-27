<?php

namespace Tests\Feature\Vendedor;

use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\ServicioContratado;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PruebaPlanNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\PruebaAperturaService;
use App\Services\Pedidos\SplitMultivendedorService;
use App\Support\AjustesMonetizacion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromocionAperturaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(AjustesMonetizacion::CACHE);
        AjustesMonetizacion::aplicar();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function activar(int $dias = 30, ?string $hasta = null): void
    {
        $this->actingAs($this->admin)->put(route('admin.precios-planes.apertura'), ['activa' => '1', 'dias' => $dias, 'hasta' => $hasta])
            ->assertSessionHasNoErrors();
    }

    private function vendedor(array $datos = []): Vendor
    {
        $vendor = Vendor::factory()->approved()->create(array_replace(['plan' => 'inicial', 'plan_hasta' => null, 'commission_percentage' => 7], $datos));
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_apagada_por_defecto_y_el_admin_la_activa(): void
    {
        $this->assertFalse(PruebaAperturaService::vigente());

        $this->activar(30, now()->addMonths(2)->toDateString());

        $this->assertTrue(PruebaAperturaService::vigente());
        $this->assertSame(30, PruebaAperturaService::dias());
        $this->assertFalse(PruebaAperturaService::vigente(now()->addMonths(3)), 'Después de la fecha límite ya no se ofrece.');

        $this->actingAs($this->admin)->get(route('admin.precios-planes.index'))->assertOk()->assertSee('Promoción de apertura')->assertSee('Activa');
        $this->get(route('vende'))->assertOk()->assertSee('Tu primer mes gratis en cualquier plan')->assertSee('30 días gratis, luego Q 199/mes');
        $this->get(route('servicios'))->assertSee('Primer mes gratis');
    }

    public function test_al_aprobar_una_tienda_empieza_su_mes_gratis_con_el_plan_elegido(): void
    {
        Notification::fake();
        $this->activar();
        $vendor = Vendor::factory()->pending()->create(['plan' => 'profesional']);
        $vendor->user->assignRole('vendedor');

        $this->actingAs($this->admin)->patch(route('admin.vendedores.approve', $vendor), ['commission_percentage' => 7, 'monthly_rent' => 0])
            ->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame('profesional', $vendor->prueba_plan);
        $this->assertTrue($vendor->enPrueba());
        $this->assertSame('profesional', app(PlanesVendedorService::class)->planActual($vendor));
        $this->assertTrue(app(PlanesVendedorService::class)->permiteCargaMasiva($vendor));
        $this->assertSame(200, app(PlanesVendedorService::class)->limiteProductos($vendor));
        Notification::assertSentTo($vendor->user, PruebaPlanNotification::class);
    }

    public function test_vendedor_existente_la_usa_desde_mi_plan_una_sola_vez(): void
    {
        $this->activar();
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.plan.index'))->assertOk()->assertSee('Promoción de apertura')->assertSee('Probar 30 días gratis');

        $this->actingAs($vendor->user)->post(route('vendedor.plan.prueba'), ['plan' => 'esencial'])->assertSessionHas('success');
        $vendor->refresh();
        $this->assertSame('esencial', app(PlanesVendedorService::class)->planActual($vendor));
        $this->assertSame(0, ServicioContratado::query()->count(), 'No se cobra nada para empezar.');

        $this->actingAs($vendor->user)->get(route('vendedor.plan.index'))->assertSee('Prueba gratis de apertura')->assertDontSee('Probar 30 días gratis');

        /* Una sola vez: no puede volver a empezarla con otro plan. */
        $this->actingAs($vendor->user)->post(route('vendedor.plan.prueba'), ['plan' => 'avanzado'])->assertSessionHas('error');
        $this->assertSame('esencial', $vendor->fresh()->plan);
    }

    public function test_al_terminar_sin_pagar_pasa_al_plan_inicial_sin_cerrar(): void
    {
        Notification::fake();
        $this->activar();
        $vendor = app(PruebaAperturaService::class)->iniciar($this->vendedor(), 'avanzado');

        $this->travel(29)->days();
        $this->assertSame(1, app(PruebaAperturaService::class)->avisar());
        $this->assertSame(0, app(PruebaAperturaService::class)->avisar(), 'No repite el aviso.');

        $this->travel(2)->days();
        $this->assertSame(1, app(PruebaAperturaService::class)->avisar());
        Notification::assertSentToTimes($vendor->user, PruebaPlanNotification::class, 2);

        /* 3 días de gracia y luego el vencimiento normal lo baja al Inicial. */
        $this->travel(4)->days();
        app(PlanesVendedorService::class)->bajarVencidos();
        $vendor->refresh();
        $this->assertSame('inicial', $vendor->plan);
        $this->assertSame('approved', $vendor->status);
        $this->assertEquals(7, (float) $vendor->commission_percentage);
    }

    public function test_si_paga_durante_la_prueba_el_mes_pagado_empieza_al_terminar(): void
    {
        $this->activar();
        $vendor = app(PruebaAperturaService::class)->iniciar($this->vendedor(), 'esencial');
        $finPrueba = $vendor->prueba_hasta->copy();

        $this->travel(10)->days();
        app(PlanesVendedorService::class)->aplicar($vendor->fresh(), 'esencial', 'sub_123');

        $this->assertTrue($vendor->fresh()->plan_hasta->isSameDay($finPrueba->copy()->addMonthNoOverflow()));
    }

    public function test_en_el_plan_inicial_no_se_cobra_comision_durante_la_prueba(): void
    {
        $this->activar();
        $vendor = app(PruebaAperturaService::class)->iniciar($this->vendedor(), 'inicial');

        $this->assertSame(0.0, $vendor->comisionVigente());
        $this->assertEquals(0.0, (float) $this->venta($vendor)->comision_atlantia);

        $this->travel(31)->days();
        $this->assertEquals(7.0, $vendor->fresh()->comisionVigente());
        $this->assertEquals(7.0, (float) $this->venta($vendor->fresh())->comision_atlantia);
    }

    public function test_durante_la_prueba_si_se_cobra_la_tarifa_de_la_pasarela(): void
    {
        $this->activar();
        $vendor = app(PruebaAperturaService::class)->iniciar($this->vendedor(), 'inicial');

        /* Q100 con tarjeta: comisión de Atlantia 0, pero la pasarela sí (4.5 % + Q2 = 6.50). */
        $split = $this->venta($vendor, 'tarjeta');
        $this->assertEquals(0.0, (float) $split->comision_atlantia);
        $this->assertEqualsWithDelta(6.50, (float) $split->comision_pasarela, 0.001);
        $this->assertEqualsWithDelta(93.50, (float) $split->monto_neto_vendedor, 0.001);
    }

    public function test_la_tienda_atlantia_y_la_promo_apagada_no_ofrecen_prueba(): void
    {
        $vendor = $this->vendedor();
        $this->assertFalse(app(PruebaAperturaService::class)->disponiblePara($vendor));

        $this->activar();
        $atlantia = $this->vendedor(['slug' => config('atlantia.vendor_propio_slug', 'atlantia-supermarket')]);
        $this->assertFalse(app(PruebaAperturaService::class)->disponiblePara($atlantia));
        $this->assertTrue(app(PruebaAperturaService::class)->disponiblePara($vendor));
    }

    /**
     * Venta de Q100 en productos con tarjeta: devuelve la parte de la tienda.
     */
    private function venta(Vendor $vendor, string $metodo = 'efectivo'): PaymentSplit
    {
        $cliente = User::factory()->create();
        $padre = Pedido::factory()->create(['cliente_id' => $cliente->id, 'vendor_id' => null, 'total' => 125, 'envio' => 25]);
        Pedido::factory()->create(['cliente_id' => $cliente->id, 'vendor_id' => $vendor->id, 'pedido_padre_id' => $padre->id, 'total' => 125, 'envio' => 25]);
        $payment = Payment::query()->create(['uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'metodo' => $metodo, 'monto' => 125, 'estado' => 'aprobado']);

        app(SplitMultivendedorService::class)->crearSplitsDePago($payment, $padre);

        return PaymentSplit::query()->where('payment_id', $payment->id)->firstOrFail();
    }
}
