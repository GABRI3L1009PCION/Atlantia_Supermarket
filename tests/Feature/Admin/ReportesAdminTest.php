<?php

namespace Tests\Feature\Admin;

use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->admin()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_report_excludes_cancelled_orders_from_sales(): void
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Tienda Reporte']);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'total' => 100, 'created_at' => now()]);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'cancelado', 'total' => 900, 'created_at' => now()]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.reportes.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Reportes')
            ->assertSee('Q 100.00')
            ->assertDontSee('Q 1,000.00')
            ->assertSee('Tienda Reporte')
            ->assertSee('Descargar Excel')
            ->assertDontSee('Generar');
    }

    public function test_report_downloads_as_csv(): void
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Tienda CSV']);
        Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado', 'total' => 55.5, 'created_at' => now()]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.reportes.exportar', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Ventas (Q)",55.50', $csv);
        $this->assertStringContainsString('Tienda CSV', $csv);
    }
}
