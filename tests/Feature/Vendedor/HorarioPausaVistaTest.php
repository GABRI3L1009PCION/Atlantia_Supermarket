<?php

namespace Tests\Feature\Vendedor;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HorarioPausaVistaTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create(['hora_apertura' => '07:00', 'hora_cierre' => '21:00']);
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_page_shows_state_pause_options_and_schedule(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.operacion.edit'))
            ->assertOk()
            ->assertSee('Horario y pausa')
            ->assertSee('Abierta, recibiendo pedidos')
            ->assertSee('Pausar la tienda')
            ->assertSee('Abres a las')
            ->assertSee('Sonido al llegar un pedido');
    }

    public function test_paused_store_offers_resume(): void
    {
        $vendor = $this->vendedor();
        $vendor->update(['pausado_hasta' => now()->addHour()]);

        $this->actingAs($vendor->user)->get(route('vendedor.operacion.edit'))
            ->assertOk()
            ->assertSee('En pausa: no recibes pedidos')
            ->assertSee('Reanudar pedidos ahora')
            ->assertDontSee('Pausar la tienda');
    }
}
