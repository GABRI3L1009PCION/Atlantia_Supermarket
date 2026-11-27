<?php

namespace Tests\Feature\Vendedor;

use App\Models\DeliveryZone;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ZonasEntregaVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_zones_are_grouped_by_town_and_saving_activates_only_checked_ones(): void
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');
        $centro = DeliveryZone::query()->create(['uuid' => (string) Str::uuid(), 'nombre' => 'Barrio El Centro', 'slug' => 'barrio-el-centro', 'municipio' => 'Puerto Barrios', 'costo_base' => 15, 'activa' => true]);
        $puerto = DeliveryZone::query()->create(['uuid' => (string) Str::uuid(), 'nombre' => 'Área Portuaria', 'slug' => 'area-portuaria', 'municipio' => 'Santo Tomas', 'costo_base' => 20, 'activa' => true]);

        $this->actingAs($vendor->user)->get(route('vendedor.zonas-entrega.index'))
            ->assertOk()
            ->assertSee('Puerto Barrios')
            ->assertSee('Barrio El Centro')
            ->assertSee('Activar todas')
            ->assertSee('Tu tienda no tiene zonas activas.')
            ->assertDontSee('Base super')
            ->assertDontSee('Cobertura estimada');

        $this->actingAs($vendor->user)->put(route('vendedor.zonas-entrega.sync'), [
            'zonas' => [
                ['delivery_zone_id' => $centro->id, 'activa' => '1', 'costo_override' => '12.50', 'tiempo_estimado_min' => 25],
                ['delivery_zone_id' => $puerto->id, 'activa' => '0', 'costo_override' => '20', 'tiempo_estimado_min' => 30],
            ],
        ])->assertSessionHasNoErrors();

        $activas = $vendor->deliveryZones()->wherePivot('activa', true)->pluck('delivery_zones.id')->all();
        $this->assertSame([$centro->id], $activas);

        $this->actingAs($vendor->user)->get(route('vendedor.zonas-entrega.index'))
            ->assertSee('1</span> de 2 zonas activas', false)
            ->assertDontSee('Tu tienda no tiene zonas activas.');
    }
}
