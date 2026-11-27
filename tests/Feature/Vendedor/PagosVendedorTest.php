<?php

namespace Tests\Feature\Vendedor;

use App\Models\LiquidacionVendedor;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use App\Services\Pedidos\SplitMultivendedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PagosVendedorTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(bool $conCuenta = true): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create(['commission_percentage' => 7]);
        $vendor->user->assignRole('vendedor');

        /* La fabrica ya crea el perfil fiscal: solo se ajusta la cuenta bancaria. */
        VendorFiscalProfile::query()->updateOrCreate(['vendor_id' => $vendor->id], [
            'nit' => (string) random_int(1000000, 9999999), 'razon_social' => 'Tienda Prueba',
            'direccion_fiscal' => 'Puerto Barrios', 'regimen_sat' => 'general', 'codigo_establecimiento' => '1',
            'banco_nombre' => $conCuenta ? 'Banrural' : null,
            'cuenta_bancaria' => $conCuenta ? '3001234567' : null,
            'cuenta_bancaria_titular' => $conCuenta ? 'Tienda Prueba' : null,
        ]);

        return $vendor;
    }

    /**
     * Una venta de la tienda: el pedido hijo y su parte del pago.
     */
    private function venta(Vendor $vendor, float $productos, string $metodo, string $estadoPedido = 'entregado', int $tiendasEnPago = 1): PaymentSplit
    {
        $cliente = User::factory()->create();
        $padre = Pedido::factory()->create(['cliente_id' => $cliente->id, 'vendor_id' => null, 'total' => $productos + 25, 'envio' => 25]);
        $hijo = Pedido::factory()->create([
            'cliente_id' => $cliente->id, 'vendor_id' => $vendor->id, 'pedido_padre_id' => $padre->id,
            'total' => $productos + 25, 'envio' => 25, 'estado' => $estadoPedido,
        ]);
        $payment = Payment::query()->create(['uuid' => (string) Str::uuid(), 'pedido_id' => $padre->id, 'metodo' => $metodo, 'monto' => $productos + 25, 'estado' => 'aprobado']);
        $comision = round($productos * 0.07, 2);
        $pasarela = SplitMultivendedorService::tarifaPasarela($metodo, $productos, $tiendasEnPago);

        return PaymentSplit::query()->create([
            'payment_id' => $payment->id, 'vendor_id' => $vendor->id, 'pedido_id' => $hijo->id,
            'monto_bruto' => $productos, 'comision_atlantia' => $comision, 'comision_pasarela' => $pasarela,
            'monto_neto_vendedor' => round($productos - $comision - $pasarela, 2), 'estado' => 'pendiente',
        ]);
    }

    public function test_gateway_fee_is_charged_per_transaction_not_on_the_total(): void
    {
        /* Q100 con tarjeta: 4.5 % = 4.50 + Q2 = 6.50. */
        $this->assertEqualsWithDelta(6.50, SplitMultivendedorService::tarifaPasarela('tarjeta', 100, 1), 0.001);
        /* Mismo pago con dos tiendas: el fijo de Q2 se divide. */
        $this->assertEqualsWithDelta(5.50, SplitMultivendedorService::tarifaPasarela('tarjeta', 100, 2), 0.001);
        /* Efectivo y transferencia no pagan pasarela. */
        $this->assertSame(0.0, SplitMultivendedorService::tarifaPasarela('efectivo', 100));
        $this->assertSame(0.0, SplitMultivendedorService::tarifaPasarela('transferencia', 100));

        /* Dos ventas de Q50 con tarjeta pagan dos fijos (no uno sobre el total de Q100). */
        $vendor = $this->vendedor();
        $this->venta($vendor, 50, 'tarjeta');
        $this->venta($vendor, 50, 'tarjeta');
        $this->assertEqualsWithDelta(8.50, (float) PaymentSplit::query()->sum('comision_pasarela'), 0.001);
    }

    public function test_vendor_requests_payment_and_balance_resets_when_paid(): void
    {
        $vendor = $this->vendedor();
        $this->venta($vendor, 100, 'tarjeta');     // 100 - 7 - 6.50 = 86.50
        $this->venta($vendor, 200, 'efectivo');    // 200 - 14 = 186.00
        $this->venta($vendor, 80, 'tarjeta', 'en_ruta'); // aun no entregado: no se puede cobrar

        $this->actingAs($vendor->user)->get(route('vendedor.pagos.index'))
            ->assertOk()
            ->assertSee('Q 272.50')
            ->assertSee('Se calcula <strong>venta por venta</strong>', false);

        $this->actingAs($vendor->user)->post(route('vendedor.pagos.solicitar'))->assertRedirect();
        $liquidacion = LiquidacionVendedor::query()->firstOrFail();
        $this->assertSame(2, $liquidacion->ventas);
        $this->assertEqualsWithDelta(272.50, (float) $liquidacion->monto_neto, 0.001);

        /* Ya solicitado: el saldo disponible queda en cero y no se puede pedir dos veces. */
        $this->actingAs($vendor->user)->post(route('vendedor.pagos.solicitar'))->assertSessionHasErrors('pago');

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('admin.liquidaciones.pagar', $liquidacion), ['referencia_pago' => 'TRX-998'])->assertRedirect();

        $this->assertSame('pagada', $liquidacion->fresh()->estado);
        $this->assertSame(2, PaymentSplit::query()->where('estado', 'liquidado')->count());
        $this->actingAs($vendor->user)->get(route('vendedor.pagos.show', $liquidacion))->assertOk()->assertSee('TRX-998');
    }

    public function test_rejected_request_returns_sales_to_balance(): void
    {
        $vendor = $this->vendedor();
        $this->venta($vendor, 100, 'efectivo');
        $this->actingAs($vendor->user)->post(route('vendedor.pagos.solicitar'));
        $liquidacion = LiquidacionVendedor::query()->firstOrFail();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('admin.liquidaciones.rechazar', $liquidacion), ['notas' => 'Cuenta inválida']);

        $this->assertSame('rechazada', $liquidacion->fresh()->estado);
        $this->assertNull(PaymentSplit::query()->first()->liquidacion_id);
    }

    public function test_vendor_without_bank_account_cannot_request(): void
    {
        $vendor = $this->vendedor(false);
        $this->venta($vendor, 100, 'efectivo');

        $this->actingAs($vendor->user)->post(route('vendedor.pagos.solicitar'))->assertSessionHas('error');
        $this->assertSame(0, LiquidacionVendedor::query()->count());
    }
}
