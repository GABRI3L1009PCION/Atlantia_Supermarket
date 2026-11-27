<?php

namespace Tests\Feature\Cliente;

use App\Models\DeliveryRoute;
use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoEnVivoTest extends TestCase
{
    use RefreshDatabase;

    private function pedidoConRepartidor(string $estado): Pedido
    {
        Role::findOrCreate('cliente', 'web');
        $pedido = Pedido::factory()->create(['estado' => $estado]);
        $pedido->cliente->assignRole('cliente');
        $repartidor = User::factory()->create(['name' => 'Carlos Méndez']);

        DeliveryRoute::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'repartidor_id' => $repartidor->id,
            'pickup_name' => 'Punto de recogida',
            'pickup_latitude' => 15.6866,
            'pickup_longitude' => -88.6118,
            /* Estado de la ruta (enum de delivery_routes), no del pedido. */
            'estado' => 'iniciada',
        ]);

        MarketCourierStatus::query()->create([
            'repartidor_id' => $repartidor->id,
            'pedido_id' => $pedido->id,
            'latitude' => 15.7290,
            'longitude' => -88.5960,
            'timestamp_gps' => now(),
            'estado' => 'en_ruta',
        ]);

        return $pedido;
    }

    public function test_customer_sees_courier_location_while_order_is_on_the_way(): void
    {
        $pedido = $this->pedidoConRepartidor('en_ruta');

        $this->actingAs($pedido->cliente)
            ->getJson(route('cliente.pedidos.seguimiento.live', $pedido))
            ->assertOk()
            ->assertJsonPath('data.pedido.estado', 'en_ruta')
            ->assertJsonPath('data.repartidor.latitude', 15.729)
            ->assertJsonMissingPath('data.ruta_real')
            ->assertJsonMissingPath('data.ruta_planificada');

        $this->actingAs($pedido->cliente)
            ->get(route('cliente.pedidos.seguimiento', $pedido))
            ->assertOk()
            ->assertSee('Tu pedido va en camino')
            ->assertSee('Carlos M.')
            ->assertSee('vendor/leaflet/leaflet.js')
            ->assertDontSee('mapbox');
    }

    public function test_courier_location_is_hidden_before_pickup(): void
    {
        $pedido = $this->pedidoConRepartidor('preparando');

        $this->actingAs($pedido->cliente)
            ->getJson(route('cliente.pedidos.seguimiento.live', $pedido))
            ->assertOk()
            ->assertJsonPath('data.repartidor', null);

        $this->actingAs($pedido->cliente)
            ->get(route('cliente.pedidos.seguimiento', $pedido))
            ->assertOk()
            ->assertSee('Preparando tu pedido')
            ->assertDontSee('Punto de recogida')
            ->assertDontSee('15.6866');
    }

    public function test_other_customers_cannot_track_the_order(): void
    {
        $pedido = $this->pedidoConRepartidor('en_ruta');

        $otro = User::factory()->create();
        $otro->assignRole('cliente');

        $this->actingAs($otro)
            ->getJson(route('cliente.pedidos.seguimiento.live', $pedido))
            ->assertForbidden();
    }
}
