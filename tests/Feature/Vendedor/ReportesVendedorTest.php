<?php

namespace Tests\Feature\Vendedor;

use App\Models\Pedido;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesVendedorTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_sales_exclude_cancelled_orders_shipping_and_tips_and_compare_with_real_previous_period(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));
        $vendor = $this->vendedor();
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'total' => 115, 'envio' => 15, 'propina' => 0, 'created_at' => now()->subDays(1)]);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'cancelado', 'total' => 500, 'envio' => 0, 'propina' => 0, 'created_at' => now()->subDays(1)]);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'total' => 50, 'envio' => 0, 'propina' => 0, 'created_at' => now()->subDays(8)]);

        $this->actingAs($vendor->user)->get(route('vendedor.reportes.index', ['fecha_desde' => now()->subDays(6)->toDateString(), 'fecha_hasta' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Q 100.00')
            ->assertSee('▲ 100.0 % vs. los 7 días anteriores')
            ->assertSee('1 cancelados')
            ->assertDontSee('8.4%')
            ->assertDontSee('Atlantia Supermarket');
    }

    public function test_seller_downloads_sales_file_for_excel(): void
    {
        $vendor = $this->vendedor();
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'metodo_pago' => 'efectivo', 'total' => 115, 'envio' => 15, 'propina' => 0, 'numero_pedido' => 'ATL-PRUEBA-1']);

        $respuesta = $this->actingAs($vendor->user)->get(route('vendedor.reportes.descargar'));
        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', $respuesta->headers->get('Content-Type'));
        $contenido = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('ATL-PRUEBA-1;Entregado;Efectivo', $contenido);
        $this->assertStringContainsString('100.00;15.00;115.00', $contenido);
    }
}
