<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para vendedores locales de Izabal.
 *
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Vendor>
     */
    protected $model = Vendor::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->randomElement([
            'Abarroteria El Malecon',
            'Despensa Santo Tomas',
            'Mercado Familiar Izabal',
            'Mariscos Bahia Amatique',
            'Lacteos La Ruidosa',
            'Frutas del Caribe',
            'Carniceria Puerto Libre',
            'Super Tienda La Bendicion',
        ]);
        $municipio = fake()->randomElement([
            'Puerto Barrios',
            'Santo Tomas',
            'Morales',
            'Los Amates',
            'Livingston',
            'El Estor',
        ]);
        $coordinates = $this->coordinatesForMunicipio($municipio);

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory()->vendedor(),
            'business_name' => $businessName,
            'slug' => Str::slug($businessName . '-' . fake()->unique()->numberBetween(100, 999)),
            'descripcion' => fake()->randomElement([
                'Tienda local con productos frescos y abarrotes de consumo diario.',
                'Comercio familiar con entregas en zonas cercanas de Izabal.',
                'Proveedor local de productos seleccionados para el hogar.',
            ]),
            'logo_path' => 'vendors/logos/' . Str::slug($businessName) . '.webp',
            'cover_path' => 'vendors/covers/' . Str::slug($businessName) . '.webp',
            'telefono_publico' => '+502 ' . fake()->numerify('####-####'),
            'email_publico' => Str::slug($businessName) . '@atlantia.local',
            'municipio' => $municipio,
            'direccion_comercial' => fake()->randomElement([
                'Calzada Justo Rufino Barrios, local 4',
                'Avenida Simon Bolivar, zona comercial',
                'Barrio El Centro, frente al parque municipal',
                'Carretera al Atlantico, local familiar',
            ]),
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'is_approved' => true,
            'approved_by' => null,
            'approved_at' => now()->subDays(fake()->numberBetween(5, 90)),
            'suspendido_at' => null,
            'suspendido_por' => null,
            'motivo_suspension' => null,
            'status' => 'approved',
            'commission_percentage' => fake()->randomElement([5.00, 7.50, 10.00]),
            'monthly_rent' => fake()->randomElement([150.00, 250.00, 350.00]),
            'accepts_cash' => true,
            'accepts_transfer' => true,
            'accepts_card' => true,
        ];
    }

    /**
     * Estado para vendedores aprobados.
     *
     * @return static
     */
    public function approved(): static
    {
        return $this->state(fn (): array => [
            'is_approved' => true,
            'status' => 'approved',
            'approved_at' => now(),
            'suspendido_at' => null,
            'motivo_suspension' => null,
        ]);
    }

    /**
     * Estado para vendedores pendientes.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'is_approved' => false,
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    /**
     * Estado para vendedores suspendidos.
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'is_approved' => false,
            'status' => 'suspended',
            'suspendido_at' => now(),
            'motivo_suspension' => 'Revision administrativa por documentos fiscales pendientes.',
        ]);
    }

    /**
     * Coordenadas aproximadas por municipio de Izabal.
     *
     * @param string $municipio
     * @return array<string, float>
     */
    private function coordinatesForMunicipio(string $municipio): array
    {
        return match ($municipio) {
            'Santo Tomas' => ['latitude' => 15.69690000, 'longitude' => -88.62060000],
            'Morales' => ['latitude' => 15.47250000, 'longitude' => -88.84090000],
            'Los Amates' => ['latitude' => 15.25660000, 'longitude' => -89.09730000],
            'Livingston' => ['latitude' => 15.82830000, 'longitude' => -88.75060000],
            'El Estor' => ['latitude' => 15.53330000, 'longitude' => -89.35000000],
            default => ['latitude' => 15.73090000, 'longitude' => -88.59440000],
        };
    }
}
