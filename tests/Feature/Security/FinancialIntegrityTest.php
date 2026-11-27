<?php

namespace Tests\Feature\Security;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\Devolucion;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Pagos\PasarelaPagoService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public static function excessiveRefunds(): array
    {
        return [[1000, 28], [29, 28], [25, 20]];
    }

    #[DataProvider('excessiveRefunds')]
    public function test_refund_is_limited_by_both_order_and_collected_payment(float $refund, float $paid): void
    {
        [$admin, $pedido, $payment, $devolucion] = $this->refundContext($paid);
        $this->actingAs($admin)->patchJson(route('admin.devoluciones.update', $devolucion), [
            'decision' => 'aprobada', 'monto_reembolso' => $refund,
        ])->assertUnprocessable()->assertJsonValidationErrors('monto_reembolso');

        $this->assertSame('solicitada', $devolucion->fresh()->estado);
        $this->assertSame(EstadoPago::Aprobado, $payment->fresh()->estado);
        $this->assertEquals(0, $devolucion->fresh()->monto_reembolso);
        $this->assertSame('entregado', $pedido->fresh()->estadoValor());
    }

    public function test_uncollected_payment_cannot_be_refunded(): void
    {
        [$admin, , $payment, $devolucion] = $this->refundContext(28);
        $payment->update(['estado' => EstadoPago::Pendiente]);
        $this->actingAs($admin)->patchJson(route('admin.devoluciones.update', $devolucion), [
            'decision' => 'aprobada', 'monto_reembolso' => 25,
        ])->assertUnprocessable();
        $this->assertSame('solicitada', $devolucion->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $payment->fresh()->estado);
    }

    public function test_payment_service_rejects_a_second_refund(): void
    {
        [, , $payment] = $this->refundContext(28);
        $service = app(PasarelaPagoService::class);
        $service->reembolsar($payment, 25);
        $this->expectException(ValidationException::class);
        $service->reembolsar($payment, 1);
    }

    public function test_processing_webhook_does_not_mark_payment_as_collected(): void
    {
        [, , $payment] = $this->refundContext(28);
        $payment->update(['estado' => EstadoPago::Pendiente]);
        app(PasarelaPagoService::class)->confirmarDesdeWebhook([
            'payment_uuid' => $payment->uuid, 'status' => 'processing',
        ], []);
        $this->assertSame(EstadoPago::Validando, $payment->fresh()->estado);
    }

    public function test_old_webhook_cannot_approve_a_refunded_payment_again(): void
    {
        [, , $payment] = $this->refundContext(28);
        $payment->update(['estado' => EstadoPago::Reembolsado]);
        app(PasarelaPagoService::class)->confirmarDesdeWebhook([
            'payment_uuid' => $payment->uuid, 'status' => 'succeeded',
        ], []);
        $this->assertSame(EstadoPago::Reembolsado, $payment->fresh()->estado);
    }

    private function refundContext(float $paid): array
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        $pedido = Pedido::factory()->entregado()->create(['total' => 28, 'metodo_pago' => MetodoPago::Efectivo]);
        $payment = Payment::factory()->aprobado()->create([
            'pedido_id' => $pedido->id, 'monto' => $paid, 'metodo' => MetodoPago::Efectivo,
        ]);
        $devolucion = Devolucion::factory()->create(['pedido_id' => $pedido->id, 'user_id' => $pedido->cliente_id]);

        return [$admin, $pedido, $payment, $devolucion];
    }
}
