<?php

namespace Tests\Feature\Checkout;

use App\Livewire\Checkout\ResumenMultivendedor;
use App\Models\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuestShippingEstimateTest extends TestCase
{
    use RefreshDatabase;

    private function zona(string $municipio, float $costo, float $lat, float $lng): void
    {
        DeliveryZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'nombre' => $municipio.' centro',
            'slug' => Str::slug($municipio.' centro'),
            'descripcion' => 'Zona de prueba.',
            'municipio' => $municipio,
            'costo_base' => $costo,
            'latitude_centro' => $lat,
            'longitude_centro' => $lng,
            'activa' => true,
        ]);
    }

    public function test_guest_summary_estimates_shipping_from_selected_town_and_pickup_is_free(): void
    {
        $this->zona('Puerto Barrios', 18, 15.7309, -88.5944);
        $this->zona('Santo Tomas', 20, 15.6969, -88.6155);

        Livewire::test(ResumenMultivendedor::class)
            ->assertViewHas('envio', 0.0)
            ->assertViewHas('envioPendiente', true)
            /* Elegir solo el municipio no basta: el envio espera a que escriba la direccion. */
            ->dispatch('checkout.entrega-actualizada', tipoEntrega: 'domicilio', municipio: 'Puerto Barrios')
            ->assertViewHas('envioPendiente', true)
            ->dispatch('checkout.entrega-actualizada', tipoEntrega: 'domicilio', municipio: 'Puerto Barrios', direccion: '5a avenida, casa 12')
            ->assertViewHas('envio', 18.0)
            ->assertViewHas('envioPendiente', false)
            ->dispatch('checkout.entrega-actualizada', tipoEntrega: 'programado', municipio: 'Santo Tomas', direccion: '5a avenida, casa 12')
            ->assertViewHas('envio', 20.0)
            ->dispatch('checkout.entrega-actualizada', tipoEntrega: 'recoger', municipio: 'Santo Tomas')
            ->assertViewHas('envio', 0.0);
    }
}
