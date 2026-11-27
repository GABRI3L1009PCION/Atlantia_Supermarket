<?php

namespace Tests\Feature\Vendedor;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ComisionesFacturasVendedorTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_commissions_page_shows_monthly_summary_and_detail(): void
    {
        $vendor = $this->vendedor();
        $comision = VendorCommission::query()->create([
            'uuid' => (string) Str::uuid(), 'vendor_id' => $vendor->id, 'anio' => 2026, 'mes' => 10,
            'total_ventas' => 1500, 'commission_percentage' => 7, 'monto_comision' => 105, 'renta_fija' => 0,
            'monto_total' => 105, 'estado' => 'pendiente', 'fecha_emision' => '2026-11-01', 'fecha_vencimiento' => '2026-11-15',
        ]);

        $this->actingAs($vendor->user)->get(route('vendedor.comisiones.index'))
            ->assertOk()
            ->assertSee('Octubre 2026')
            ->assertSee('Q1,500.00')
            ->assertSee('Por cobrar')
            ->assertSee('Q105.00');

        $this->actingAs($vendor->user)->get(route('vendedor.comisiones.show', $comision))
            ->assertOk()
            ->assertSee('Comisión (7 % de tus ventas)');
    }

    public function test_invoices_page_lists_filters_and_cancels(): void
    {
        $vendor = $this->vendedor();
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id]);
        $dte = DteFactura::factory()->certificado()->create(['vendor_id' => $vendor->id, 'pedido_id' => $pedido->id, 'monto_total' => 149]);

        $this->actingAs($vendor->user)->get(route('vendedor.dte.index'))
            ->assertOk()
            ->assertSee('Facturas')
            ->assertSee('Válida')
            ->assertSee('#'.$pedido->numero_pedido);
        $this->actingAs($vendor->user)->get(route('vendedor.dte.index', ['estado' => 'anulado']))
            ->assertOk()
            ->assertSee('No hay facturas con esos filtros');

        $this->actingAs($vendor->user)->get(route('vendedor.dte.show', $dte))
            ->assertOk()
            ->assertSee('Anular esta factura');
    }
}
