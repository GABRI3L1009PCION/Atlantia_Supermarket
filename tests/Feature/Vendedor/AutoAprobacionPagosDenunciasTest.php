<?php

namespace Tests\Feature\Vendedor;

use App\Models\DenunciaTienda;
use App\Models\LiquidacionVendedor;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use App\Notifications\DenunciaTiendaNotification;
use App\Notifications\PagoTiendaPendienteNotification;
use App\Notifications\VendedorAprobadoNotification;
use App\Services\Vendedores\LiquidacionesVendedorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AutoAprobacionPagosDenunciasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function solicitud(array $cambios = []): array
    {
        return array_replace([
            'name' => 'María López Pérez', 'email' => 'maria@example.test', 'phone' => '+502 4724 0606', 'birthdate' => '1990-01-01',
            'address_street' => 'Avenida Principal', 'address_number' => '12-34', 'address_municipio' => 'Puerto Barrios', 'address_colonia' => 'Barrio El Centro', 'address_departamento' => 'Izabal',
            'document_type' => 'dpi', 'document_number' => '2345678901234',
            'document_front' => UploadedFile::fake()->image('dpi-frente.jpg'), 'document_back' => UploadedFile::fake()->image('dpi-reverso.jpg'),
            'business_name' => 'Tienda Doña María', 'business_description' => 'Abarrotes y productos de consumo diario.',
            'business_category' => 'alimentos_frescos', 'business_logo' => UploadedFile::fake()->image('logo.jpg'),
            'has_nit' => false, 'seller_plan' => 'profesional',
            'bank' => 'Banrural', 'account_type' => 'ahorros', 'account_number' => '3001234567', 'account_holder' => 'María López Pérez',
            'bank_proof' => UploadedFile::fake()->image('banco.jpg'),
            'payment_frequency' => 'instantanea', 'preferred_payment_method' => 'transferencia',
            'terms' => '1', 'truth' => '1', 'data_consent' => '1',
        ], $cambios);
    }

    public function test_si_cumple_los_requisitos_la_tienda_se_aprueba_sola_y_recibe_su_acceso(): void
    {
        Notification::fake();

        $this->get(route('vendedor.solicitar.create', ['plan' => 'profesional']))->assertOk()->assertSee('Plan Profesional')->assertSee('ahí lo activas con tarjeta');

        $this->post(route('vendedor.solicitar.store'), $this->solicitud())->assertRedirect()->assertSessionHas('status', fn ($m) => str_contains($m, 'aprobada'));

        $vendor = Vendor::query()->where('business_name', 'Tienda Doña María')->firstOrFail();
        $this->assertSame('approved', $vendor->status);
        $this->assertTrue($vendor->revision_automatica['aprobada']);
        $this->assertNull($vendor->approved_by);
        $this->assertSame('instantanea', $vendor->payment_frequency);
        $this->assertEquals(7, (float) $vendor->commission_percentage, 'Hasta pagar su plan vende con las condiciones del Inicial.');
        Notification::assertSentTo($vendor->user, VendedorAprobadoNotification::class);

        /* En su panel ve que su plan está pendiente de pago, con un botón para pagarlo. */
        $vendor->user->assignRole('vendedor');
        $vendor->user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($vendor->user->fresh())->get(route('vendedor.dashboard'))
            ->assertOk()->assertSee('Tu plan Profesional está pendiente de pago')->assertSee('Pagar Q349 y activar');
    }

    public function test_con_senales_de_riesgo_queda_pendiente_con_el_motivo(): void
    {
        Notification::fake();
        $otra = Vendor::factory()->approved()->create();
        VendorFiscalProfile::query()->updateOrCreate(['vendor_id' => $otra->id], ['cuenta_bancaria' => '300-123-4567']);

        $this->post(route('vendedor.solicitar.store'), $this->solicitud(['account_holder' => 'Pedro Ramírez Gómez']))->assertRedirect();

        $vendor = Vendor::query()->where('business_name', 'Tienda Doña María')->firstOrFail();
        $this->assertSame('pending', $vendor->status);
        $motivos = implode(' ', $vendor->revision_automatica['motivos']);
        $this->assertStringContainsString('cuenta bancaria ya está registrada', $motivos);
        $this->assertStringContainsString('titular de la cuenta bancaria no coincide', $motivos);
        Notification::assertNotSentTo($vendor->user, VendedorAprobadoNotification::class);
    }

    private function tienda(string $frecuencia = 'semanal', bool $nueva = false): Vendor
    {
        $vendor = Vendor::factory()->approved()->create(['payment_frequency' => $frecuencia, 'approved_at' => $nueva ? now()->subDays(2) : now()->subMonths(2)]);
        $vendor->user->assignRole('vendedor');
        VendorFiscalProfile::query()->updateOrCreate(['vendor_id' => $vendor->id], ['banco_nombre' => 'Banrural', 'cuenta_bancaria' => '3009998887', 'cuenta_bancaria_titular' => 'Tienda']);

        return $vendor->fresh();
    }

    private function ventaEntregada(Vendor $vendor, float $monto = 100): PaymentSplit
    {
        $cliente = User::factory()->create();
        $padre = Pedido::factory()->create(['cliente_id' => $cliente->id, 'vendor_id' => null, 'total' => $monto, 'envio' => 0, 'estado' => 'entregado']);
        $hijo = Pedido::factory()->create(['cliente_id' => $cliente->id, 'vendor_id' => $vendor->id, 'pedido_padre_id' => $padre->id, 'total' => $monto, 'envio' => 0, 'estado' => 'entregado']);
        $hijo->estados()->create(['estado' => 'entregado', 'notas' => null, 'usuario_id' => null]);
        $pago = Payment::query()->create(['uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'metodo' => 'efectivo', 'monto' => $monto, 'estado' => 'aprobado']);

        return PaymentSplit::query()->create([
            'payment_id' => $pago->id, 'vendor_id' => $vendor->id, 'pedido_id' => $hijo->id,
            'monto_bruto' => $monto, 'comision_atlantia' => 0, 'comision_pasarela' => 0, 'monto_neto_vendedor' => $monto, 'estado' => 'pendiente',
        ]);
    }

    public function test_la_tienda_elige_cobrar_al_instante_y_se_programa_su_pago_sin_costo(): void
    {
        Notification::fake();
        $vendor = $this->tienda('semanal');

        $this->actingAs($vendor->user)->put(route('vendedor.pagos.frecuencia'), ['frecuencia' => 'instantanea'])->assertSessionHas('success');
        $this->assertSame('instantanea', $vendor->fresh()->payment_frequency);

        $this->ventaEntregada($vendor, 150);
        $this->assertSame(1, app(LiquidacionesVendedorService::class)->programarPagosAutomaticos());

        $pago = LiquidacionVendedor::query()->firstOrFail();
        $this->assertTrue($pago->automatica);
        $this->assertEqualsWithDelta(150.0, (float) $pago->monto_neto, 0.001, 'Sin ningún cobro por transferir.');
        $this->assertTrue($pago->pagar_antes_de->between(now()->addMinutes(110), now()->addMinutes(130)));
        Notification::assertSentTo($this->admin, PagoTiendaPendienteNotification::class);

        $this->actingAs($this->admin)->get(route('admin.liquidaciones.index'))->assertOk()->assertSee('Transferir antes de');
    }

    public function test_los_pagos_semanales_se_preparan_una_vez_por_semana(): void
    {
        $vendor = $this->tienda('semanal');
        $this->ventaEntregada($vendor);
        $this->travelTo(now()->startOfWeek()->setTime(9, 0));

        $this->assertSame(1, app(LiquidacionesVendedorService::class)->programarPagosAutomaticos());
        $this->ventaEntregada($vendor);
        $this->assertSame(0, app(LiquidacionesVendedorService::class)->programarPagosAutomaticos(), 'Ya tuvo su pago de esta semana.');

        $this->travel(7)->days();
        $this->assertSame(1, app(LiquidacionesVendedorService::class)->programarPagosAutomaticos());
    }

    public function test_tienda_nueva_cobra_sus_ventas_7_dias_despues_de_entregarlas(): void
    {
        $vendor = $this->tienda('instantanea', nueva: true);
        $this->ventaEntregada($vendor, 80);
        $servicio = app(LiquidacionesVendedorService::class);

        $this->assertSame(0, $servicio->disponiblesQuery($vendor)->count());
        $this->assertSame(1, $servicio->retenidasQuery($vendor)->count());
        $this->actingAs($vendor->user)->get(route('vendedor.pagos.index'))->assertSee('Como tu tienda es nueva');

        $this->travel(8)->days();
        $this->assertSame(1, $servicio->disponiblesQuery($vendor->fresh())->count());
    }

    public function test_cliente_denuncia_y_con_tres_clientes_la_tienda_se_pausa_y_retiene_pagos(): void
    {
        Notification::fake();
        $vendor = $this->tienda();

        $clientes = collect(range(1, 3))->map(function () {
            $c = User::factory()->create();
            $c->assignRole('cliente');

            return $c;
        });

        $this->actingAs($clientes[0])->get(route('comercios.show', $vendor->slug))->assertOk()->assertSee('Denunciar esta tienda');
        $this->actingAs($clientes[0])->post(route('cliente.tiendas.denunciar', $vendor->slug), ['motivo' => 'no_entrego', 'descripcion' => 'Pagué y nunca me entregaron el pedido.'])
            ->assertSessionHas('success');
        Notification::assertSentTo($this->admin, DenunciaTiendaNotification::class);

        /* El mismo cliente no puede volver a denunciar mientras la primera está en revisión. */
        $this->actingAs($clientes[0])->post(route('cliente.tiendas.denunciar', $vendor->slug), ['motivo' => 'fraude', 'descripcion' => 'Otra vez lo mismo con esta tienda.'])
            ->assertSessionHasErrors('descripcion', null, 'denuncia');

        $this->actingAs($clientes[1])->post(route('cliente.tiendas.denunciar', $vendor->slug), ['motivo' => 'fraude', 'descripcion' => 'Me cobraron y no llegó nada.']);
        $this->assertNull($vendor->fresh()->pagos_retenidos_at);

        $this->actingAs($clientes[2])->post(route('cliente.tiendas.denunciar', $vendor->slug), ['motivo' => 'no_entrego', 'descripcion' => 'Tampoco me entregaron a mí.']);
        $vendor->refresh();
        $this->assertNotNull($vendor->pagos_retenidos_at);
        $this->assertTrue($vendor->pausado_hasta->isFuture());

        $this->ventaEntregada($vendor);
        try {
            app(LiquidacionesVendedorService::class)->solicitar($vendor);
            $this->fail('Con pagos retenidos no se debe poder pagar.');
        } catch (ValidationException) {
        }
        $this->assertSame(0, app(LiquidacionesVendedorService::class)->programarPagosAutomaticos());

        $this->actingAs($this->admin)->get(route('admin.denuncias.index'))->assertOk()->assertSee('Tiendas pausadas en revisión')->assertSee($vendor->business_name);
        $denuncia = DenunciaTienda::query()->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.denuncias.resolver', $denuncia), ['estado' => 'descartada', 'resolucion' => 'Se entregó, hubo confusión.'])->assertSessionHas('success');
        $this->assertSame('descartada', $denuncia->fresh()->estado);

        $this->actingAs($this->admin)->post(route('admin.denuncias.reactivar', $vendor))->assertSessionHas('success');
        $vendor->refresh();
        $this->assertNull($vendor->pagos_retenidos_at);
        $this->assertNull($vendor->pausado_hasta);
    }
}
