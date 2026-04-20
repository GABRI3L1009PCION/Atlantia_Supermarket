<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para productos de supermercado.
 *
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Producto>
     */
    protected $model = Producto::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $producto = fake()->randomElement([
            ['nombre' => 'Frijol negro de Oriente 2 lb', 'precio' => 18.50, 'unidad' => 'bolsa'],
            ['nombre' => 'Arroz blanco nacional 5 lb', 'precio' => 32.00, 'unidad' => 'bolsa'],
            ['nombre' => 'Cafe tostado de Guatemala 400 g', 'precio' => 42.00, 'unidad' => 'bolsa'],
            ['nombre' => 'Banano criollo por docena', 'precio' => 12.00, 'unidad' => 'docena'],
            ['nombre' => 'Queso fresco artesanal 1 lb', 'precio' => 28.00, 'unidad' => 'libra'],
            ['nombre' => 'Camaron fresco del Atlantico 1 lb', 'precio' => 58.00, 'unidad' => 'libra'],
            ['nombre' => 'Agua pura garrafon 5 galones', 'precio' => 18.00, 'unidad' => 'unidad'],
            ['nombre' => 'Detergente multiusos 1 kg', 'precio' => 24.50, 'unidad' => 'bolsa'],
        ]);

        return [
            'uuid' => (string) Str::uuid(),
            'vendor_id' => Vendor::factory()->approved(),
            'categoria_id' => fn (): int => $this->categoriaId(),
            'sku' => strtoupper(fake()->bothify('ATL-####-??')),
            'nombre' => $producto['nombre'],
            'slug' => Str::slug($producto['nombre'] . '-' . fake()->unique()->numberBetween(100, 999)),
            'descripcion' => fake()->randomElement([
                'Producto seleccionado para consumo familiar en Izabal.',
                'Disponible para entrega local por vendedor aprobado de Atlantia.',
                'Producto de rotacion frecuente en compras de supermercado.',
            ]),
            'precio_base' => $producto['precio'],
            'precio_oferta' => fake()->optional(0.25)->randomFloat(2, max(1, $producto['precio'] - 6), $producto['precio'] - 1),
            'peso_gramos' => fake()->optional(0.75)->randomElement([454, 500, 907, 1000, 2268]),
            'unidad_medida' => $producto['unidad'],
            'requiere_refrigeracion' => in_array($producto['unidad'], ['libra'], true)
                && str_contains(strtolower($producto['nombre']), 'queso'),
            'is_active' => true,
            'visible_catalogo' => true,
            'publicado_at' => now()->subDays(fake()->numberBetween(1, 45)),
        ];
    }

    /**
     * Estado para productos publicados.
     *
     * @return static
     */
    public function publicado(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
            'visible_catalogo' => true,
            'publicado_at' => now(),
        ]);
    }

    /**
     * Estado para productos no visibles.
     *
     * @return static
     */
    public function oculto(): static
    {
        return $this->state(fn (): array => [
            'visible_catalogo' => false,
        ]);
    }

    /**
     * Obtiene o crea una categoria base para pruebas.
     *
     * @return int
     */
    private function categoriaId(): int
    {
        return (int) (
            Categoria::query()->inRandomOrder()->value('id')
            ?? Categoria::query()->create([
                'nombre' => 'Abarrotes',
                'slug' => 'abarrotes',
                'descripcion' => 'Productos basicos para el hogar.',
                'icon' => 'shopping-bag',
                'orden' => 1,
                'is_active' => true,
            ])->id
        );
    }
}
