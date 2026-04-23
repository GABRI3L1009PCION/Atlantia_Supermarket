<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para items de pedido.
 *
 * @extends Factory<PedidoItem>
 */
class PedidoItemFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<PedidoItem>
     */
    protected $model = PedidoItem::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pedido_id' => Pedido::factory(),
            'producto_id' => Producto::factory()->publicado(),
            'producto_nombre_snapshot' => fake()->randomElement([
                'Pechuga de pollo fresca 500g',
                'Tortillas de maiz x12',
                'Frijol negro de Oriente 2 lb',
            ]),
            'producto_sku_snapshot' => strtoupper(fake()->bothify('ATL-###-??')),
            'cantidad' => fake()->numberBetween(1, 4),
            'precio_unitario_snapshot' => fake()->randomFloat(2, 5, 80),
            'subtotal' => fake()->randomFloat(2, 10, 200),
            'descuento' => fake()->randomElement([0, 0, 0, 5]),
            'impuestos' => fake()->randomFloat(2, 1, 24),
        ];
    }
}
