<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorias de supermercado.
 */
class CategoriaSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de categorias.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Abarrotes',
                'slug' => 'abarrotes',
                'icon' => 'shopping-bag',
                'orden' => 1,
                'children' => [
                    ['nombre' => 'Granos basicos', 'slug' => 'granos-basicos', 'icon' => 'archive-box'],
                    ['nombre' => 'Aceites y condimentos', 'slug' => 'aceites-condimentos', 'icon' => 'beaker'],
                    ['nombre' => 'Enlatados', 'slug' => 'enlatados', 'icon' => 'cube'],
                ],
            ],
            [
                'nombre' => 'Frutas y verduras',
                'slug' => 'frutas-verduras',
                'icon' => 'sparkles',
                'orden' => 2,
                'children' => [
                    ['nombre' => 'Frutas del Caribe', 'slug' => 'frutas-caribe', 'icon' => 'sun'],
                    ['nombre' => 'Verduras frescas', 'slug' => 'verduras-frescas', 'icon' => 'leaf'],
                ],
            ],
            [
                'nombre' => 'Carnes y mariscos',
                'slug' => 'carnes-mariscos',
                'icon' => 'fire',
                'orden' => 3,
                'children' => [
                    ['nombre' => 'Mariscos del Atlantico', 'slug' => 'mariscos-atlantico', 'icon' => 'water'],
                    ['nombre' => 'Carnes frescas', 'slug' => 'carnes-frescas', 'icon' => 'scale'],
                ],
            ],
            ['nombre' => 'Lacteos y huevos', 'slug' => 'lacteos-huevos', 'icon' => 'circle-stack', 'orden' => 4],
            ['nombre' => 'Bebidas', 'slug' => 'bebidas', 'icon' => 'cup', 'orden' => 5],
            ['nombre' => 'Limpieza del hogar', 'slug' => 'limpieza-hogar', 'icon' => 'sparkles', 'orden' => 6],
            ['nombre' => 'Higiene personal', 'slug' => 'higiene-personal', 'icon' => 'heart', 'orden' => 7],
            ['nombre' => 'Farmacia basica', 'slug' => 'farmacia-basica', 'icon' => 'plus-circle', 'orden' => 8],
            ['nombre' => 'Mascotas', 'slug' => 'mascotas', 'icon' => 'face-smile', 'orden' => 9],
            ['nombre' => 'Productos locales', 'slug' => 'productos-locales', 'icon' => 'map-pin', 'orden' => 10],
        ];

        foreach ($categorias as $categoriaData) {
            $children = $categoriaData['children'] ?? [];
            unset($categoriaData['children']);

            $parent = Categoria::query()->updateOrCreate(
                ['slug' => $categoriaData['slug']],
                [
                    ...$categoriaData,
                    'parent_id' => null,
                    'descripcion' => 'Categoria principal de Atlantia Supermarket para compras locales.',
                    'is_active' => true,
                ]
            );

            foreach ($children as $index => $childData) {
                Categoria::query()->updateOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        ...$childData,
                        'parent_id' => $parent->id,
                        'descripcion' => 'Subcategoria especializada para productos de Izabal.',
                        'orden' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
