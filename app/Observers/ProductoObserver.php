<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
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
        $this->bumpSearchVersion();

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
        $this->bumpSearchVersion();
        $producto->unsearchable();
    }

    /**
     * Incrementa version de cache de busqueda sin depender de flush global.
     *
     * @return void
     */
    private function bumpSearchVersion(): void
    {
        if (! Cache::has('search:version')) {
            Cache::forever('search:version', 1);
        }

        Cache::increment('search:version');
    }
}
