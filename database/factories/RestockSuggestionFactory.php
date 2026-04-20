<?php

namespace Database\Factories;

use App\Models\Ml\MlModelVersion;
use App\Models\Ml\RestockSuggestion;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para sugerencias de reabastecimiento.
 *
 * @extends Factory<RestockSuggestion>
 */
class RestockSuggestionFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<RestockSuggestion>
     */
    protected $model = RestockSuggestion::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $producto = Producto::query()->inRandomOrder()->first();
        $stockActual = fake()->numberBetween(0, 25);
        $urgencia = $stockActual <= 3 ? 'critica' : fake()->randomElement(['baja', 'media', 'alta']);

        return [
            'producto_id' => $producto?->id ?? Producto::factory()->publicado()->create()->id,
            'vendor_id' => $producto?->vendor_id
                ?? (Vendor::query()->inRandomOrder()->value('id') ?? Vendor::factory()->approved()->create()->id),
            'stock_actual' => $stockActual,
            'stock_sugerido' => fake()->numberBetween(30, 140),
            'dias_hasta_quiebre' => fake()->numberBetween(1, 14),
            'urgencia' => $urgencia,
            'aceptada' => false,
            'modelo_version_id' => fn (): ?int => MlModelVersion::query()->inRandomOrder()->value('id'),
            'aceptada_at' => null,
        ];
    }

    /**
     * Estado para sugerencia critica.
     *
     * @return static
     */
    public function critica(): static
    {
        return $this->state(fn (): array => [
            'stock_actual' => fake()->numberBetween(0, 3),
            'dias_hasta_quiebre' => fake()->numberBetween(1, 3),
            'urgencia' => 'critica',
        ]);
    }

    /**
     * Estado para sugerencia aceptada.
     *
     * @return static
     */
    public function aceptada(): static
    {
        return $this->state(fn (): array => [
            'aceptada' => true,
            'aceptada_at' => now(),
        ]);
    }
}
