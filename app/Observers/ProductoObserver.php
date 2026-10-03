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
     */
    public function creating(Producto $producto): void
    {
        if (empty($producto->uuid)) {
            $producto->uuid = (string) Str::uuid();
        }
    }

    /**
     * Sincroniza producto publicado con Scout.
     */
    public function saved(Producto $producto): void
    {
        $this->bumpSearchVersion();
        Cache::forget('categorias');

        if ($producto->is_active && $producto->visible_catalogo && $producto->publicado_at !== null) {
            $producto->searchable();

            return;
        }

        $producto->unsearchable();
    }

    /**
     * Retira producto eliminado del indice.
     */
    public function deleted(Producto $producto): void
    {
        $this->bumpSearchVersion();
        Cache::forget('categorias');
        $producto->carritoItems()->delete();
        $producto->clearMediaCollection('productos');
        $producto->unsearchable();
    }

    /**
     * Incrementa version de cache de busqueda sin depender de flush global.
     */
    private function bumpSearchVersion(): void
    {
        if (! Cache::has('search:version')) {
            Cache::forever('search:version', 1);
        }

        Cache::increment('search:version');
    }
}
