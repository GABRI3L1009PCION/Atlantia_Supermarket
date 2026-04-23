<?php

namespace Database\Factories;

use App\Models\HeroBanner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HeroBanner>
 */
class HeroBannerFactory extends Factory
{
    protected $model = HeroBanner::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'nombre' => fake()->randomElement([
                'Campana frescos de la semana',
                'Banner principal Atlantia',
                'Promocion despensa del hogar',
            ]),
            'is_active' => true,
            'orden' => fake()->numberBetween(0, 5),
            'inicia_en' => now()->subDay(),
            'termina_en' => now()->addWeek(),
        ];
    }
}
