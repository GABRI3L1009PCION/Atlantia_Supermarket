<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Str;

/**
 * Observer de productos para UUID e indice de busqueda.
 */
class ProductoObserver
{
    /**
     * Asigna UUID antes de crear.
     *
     * @param Producto $producto
     * @return void
     */
    public function creating(Producto $producto): void
    {
        if (empty($producto->uuid)) {
            $producto->uuid = (string) Str::uuid();
        }
    }

    /**
     * Sincroniza producto publicado con Scout.
     *
     * @param Producto $producto
     * @return void
     */
    public function saved(Producto $producto): void
    {
        if ($producto->is_active && $producto->visible_catalogo && $producto->publicado_at !== null) {
            $producto->searchable();

            return;
        }

        $producto->unsearchable();
    }

    /**
     * Retira producto eliminado del indice.
     *
     * @param Producto $producto
     * @return void
     */
    public function deleted(Producto $producto): void
    {
        $producto->unsearchable();
    }
}
