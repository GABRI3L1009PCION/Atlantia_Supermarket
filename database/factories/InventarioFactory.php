<?php

namespace Database\Factories;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para inventarios operativos.
 *
 * @extends Factory<Inventario>
 */
class InventarioFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Inventario>
     */
    protected $model = Inventario::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stockActual = fake()->numberBetween(5, 120);
        $stockMinimo = fake()->numberBetween(1, 10);

        return [
            'producto_id' => Producto::factory()->publicado(),
            'stock_actual' => $stockActual,
            'stock_reservado' => 0,
            'stock_minimo' => $stockMinimo,
            'stock_maximo' => max($stockActual + fake()->numberBetween(10, 100), $stockMinimo),
            'ultima_actualizacion' => now(),
        ];
    }
}
