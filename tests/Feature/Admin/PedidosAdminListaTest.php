<?php

namespace Tests\Feature\Admin;

use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidosAdminListaTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->admin()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_orders_list_shows_table_and_filters_by_status(): void
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Tienda Lista']);
        $pendiente = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'pendiente']);
        $entregado = Pedido::factory()->create(['vendor_id' => $vendor->id, 'estado' => 'entregado']);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.pedidos.index'))
            ->assertOk()
            ->assertSee('Por atender')
            ->assertSee($pendiente->numero_pedido)
            ->assertSee($entregado->numero_pedido)
            ->assertSee('Por confirmar')
            ->assertSee('Tienda Lista')
            ->assertDontSee('Atlantia Supermarket');

        $this->actingAs($admin)->get(route('admin.pedidos.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertSee($pendiente->numero_pedido)
            ->assertDontSee($entregado->numero_pedido);
    }

    public function test_batch_update_still_changes_selected_orders(): void
    {
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        $this->actingAs($this->superAdmin())->post(route('admin.pedidos.batch-update'), [
            'pedidos' => [$pedido->uuid],
            'estado' => 'confirmado',
            'estado_pago' => 'pagado',
        ])->assertSessionHasNoErrors();

        $this->assertSame('confirmado', $pedido->fresh()->estadoValor());
    }
}
