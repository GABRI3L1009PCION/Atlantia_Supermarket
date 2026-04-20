<?php

namespace Tests\Unit\Comisiones;

use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\Vendor;
use App\Services\Comisiones\CalculadoraComisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de conciliacion mensual de comisiones.
 */
class CalculadoraComisionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Calcula comision con porcentaje del vendedor y renta fija.
     */
    public function testCalculatesMonthlyCommissionFromSettledSplits(): void
    {
        $vendor = Vendor::factory()->approved()->create([
            'commission_percentage' => 8.00,
            'monthly_rent' => 150.00,
        ]);
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id]);
        $payment = Payment::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'metodo' => 'tarjeta',
            'monto' => 500.00,
            'estado' => 'aprobado',
            'hmac_validado' => true,
        ]);
        PaymentSplit::query()->create([
            'payment_id' => $payment->id,
            'vendor_id' => $vendor->id,
            'monto_bruto' => 500.00,
            'comision_atlantia' => 40.00,
            'monto_neto_vendedor' => 460.00,
            'estado' => 'liquidado',
            'created_at' => now()->setDate(2026, 4, 12),
            'updated_at' => now()->setDate(2026, 4, 12),
        ]);

        $commission = app(CalculadoraComisionService::class)->calcularMensual($vendor, 2026, 4);

        $this->assertSame('500.00', $commission->total_ventas);
        $this->assertSame('40.00', $commission->monto_comision);
        $this->assertSame('190.00', $commission->monto_total);
        $this->assertSame('pendiente', $commission->estado);
    }
}
