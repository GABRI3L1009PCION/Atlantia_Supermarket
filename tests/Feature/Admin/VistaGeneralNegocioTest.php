<?php

namespace Tests\Feature\Admin;

use App\Models\LiquidacionVendedor;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VistaGeneralNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->admin()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_super_admin_sees_business_overview_instead_of_infrastructure(): void
    {
        $this->actingAs($this->superAdmin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ventas de hoy')
            ->assertSee('Requiere tu atención')
            ->assertSee('Todo al día')
            ->assertSee('Ventas de los últimos 14 días')
            ->assertSee('Pedidos en curso')
            ->assertSee('Lo más vendido')
            ->assertDontSee('Modelos inteligentes')
            ->assertDontSee('Proceso de actualización');
    }

    public function test_overview_lists_pending_work_and_counts_todays_sales(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'pendiente', 'total' => 120.50, 'created_at' => now()->subMinutes(30)]);
        LiquidacionVendedor::query()->create([
            'uuid' => (string) Str::uuid(), 'numero' => 'LIQ-TEST-1', 'vendor_id' => $vendor->id, 'ventas' => 1,
            'monto_bruto' => 100, 'comision_plataforma' => 0, 'comision_pasarela' => 0, 'monto_neto' => 100,
            'estado' => 'solicitada', 'solicitada_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Q 120.50')
            ->assertSee('de tiendas locales sin confirmar')
            ->assertSee('1 pago a vendedor solicitado')
            ->assertSee($pedido->numero_pedido);
    }

    public function test_infrastructure_moved_to_observability(): void
    {
        $this->actingAs($this->superAdmin())->get(route('admin.observabilidad.index'))
            ->assertOk()
            ->assertSee('Proceso de actualización')
            ->assertSee('Modelos inteligentes');
    }
}
