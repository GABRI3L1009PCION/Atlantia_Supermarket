<?php

namespace Tests\Feature\Vendedor;

use App\Models\ContactMessage;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanVendedorVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_show_their_prices_and_no_raw_template_text(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create(['plan' => 'inicial']);
        $vendor->user->assignRole('vendedor');

        $respuesta = $this->actingAs($vendor->user)->get(route('vendedor.plan.index'))->assertOk();

        $respuesta->assertDontSee('@else', false)
            ->assertDontSee('@endif', false)
            ->assertSee('data-plan-actual', false)
            ->assertSee('Tu plan actual');

        foreach (config('monetizacion.planes') as $plan) {
            $precio = $plan['precio'] === null ? 'A tu medida' : ($plan['precio'] > 0 ? 'Q '.number_format($plan['precio'], 0) : 'Gratis');
            $respuesta->assertSee($precio);
        }
    }

    public function test_seller_requests_custom_plan_quote_from_panel_and_admins_are_notified(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)->get(route('vendedor.plan.index'))
            ->assertOk()
            ->assertSee('data-abrir-cotizacion', false)
            ->assertSee('Plan a tu medida')
            ->assertDontSee(route('contacto'), false);

        $this->actingAs($vendor->user)->post(route('vendedor.plan.cotizacion'), [
            'productos' => 2500,
            'telefono' => '5555 1234',
            'horario' => 'tarde',
            'mensaje' => 'Tengo dos sucursales.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $mensaje = ContactMessage::query()->where('user_id', $vendor->user->id)->firstOrFail();
        $this->assertSame('alta', $mensaje->prioridad);
        $this->assertStringContainsString('2,500', $mensaje->mensaje);
        $this->assertStringContainsString('Tengo dos sucursales.', $mensaje->mensaje);
        $this->assertSame(1, $admin->notifications()->count());

        $this->actingAs($vendor->user)->get(route('vendedor.plan.index'))
            ->assertSee('data-cotizacion-enviada', false)
            ->assertDontSee('data-abrir-cotizacion', false);

        $this->actingAs($vendor->user)->post(route('vendedor.plan.cotizacion'), ['productos' => 10, 'telefono' => '55551234']);
        $this->assertSame(1, ContactMessage::query()->where('user_id', $vendor->user->id)->count());
    }

    public function test_advertising_and_history_tabs_render(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)->get(route('vendedor.plan.index', ['tab' => 'publicidad']))
            ->assertOk()->assertSee('Oferta destacada')->assertSee('Banner en la portada');
        $this->actingAs($vendor->user)->get(route('vendedor.plan.index', ['tab' => 'historial']))
            ->assertOk()->assertSee('Aún no has contratado planes ni publicidad.');
    }
}
