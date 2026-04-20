<?php

namespace App\Services\Catalogo;

use App\Models\Producto;

class ProductoCatalogoService
{
    public function detail(Producto $producto): Producto
    {
        $relations = [];

        foreach (['imagenPrincipal', 'imagenes', 'categoria', 'vendedor', 'resenas'] as $relation) {
            if (method_exists($producto, $relation)) {
                $relations[] = $relation;
            }
        }

        if ($relations !== []) {
            $producto->loadMissing($relations);
        }

        return $producto;
    }
}
