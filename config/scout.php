<?php

use App\Models\Producto;

return [
    'driver' => env('SCOUT_DRIVER', 'collection'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', false),
    'after_commit' => env('SCOUT_AFTER_COMMIT', true),

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,
    'identify' => false,

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Producto::class => [
                'searchableAttributes' => [
                    'nombre',
                    'descripcion',
                    'sku',
                    'codigo_barras',
                ],
                'filterableAttributes' => [
                    'vendor_id',
                    'categoria_id',
                    'is_active',
                    'visible_catalogo',
                    'precio_base',
                    'precio_oferta',
                ],
                'sortableAttributes' => [
                    'precio_base',
                    'precio_oferta',
                    'id',
                ],
            ],
        ],
    ],
];
