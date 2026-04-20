<?php

namespace Database\Factories;

use App\Models\Ml\MlModelVersion;
use App\Models\Ml\SalesPrediction;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para predicciones de demanda.
 *
 * @extends Factory<SalesPrediction>
 */
class SalesPredictionFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<SalesPrediction>
     */
    protected $model = SalesPrediction::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $producto = Producto::query()->with('vendor')->inRandomOrder()->first();
        $predicho = fake()->randomFloat(2, 3, 85);

        return [
            'producto_id' => $producto?->id ?? Producto::factory()->publicado()->create()->id,
            'vendor_id' => $producto?->vendor_id
                ?? (Vendor::query()->inRandomOrder()->value('id') ?? Vendor::factory()->approved()->create()->id),
            'fecha_prediccion' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'horizonte_dias' => fake()->randomElement([7, 14, 30]),
            'valor_predicho' => $predicho,
            'valor_real' => fake()->optional(0.3)->randomFloat(2, max(0, $predicho - 8), $predicho + 8),
            'intervalo_inferior' => max(0, $predicho - fake()->randomFloat(2, 1, 8)),
            'intervalo_superior' => $predicho + fake()->randomFloat(2, 1, 10),
            'modelo_version_id' => fn (): ?int => MlModelVersion::query()->inRandomOrder()->value('id'),
        ];
    }

    /**
     * Estado para horizonte de siete dias.
     *
     * @return static
     */
    public function horizonte7(): static
    {
        return $this->state(fn (): array => [
            'horizonte_dias' => 7,
        ]);
    }
}
