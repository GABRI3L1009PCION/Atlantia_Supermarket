<?php

namespace App\Observers;

use App\Models\Categoria;
use Illuminate\Support\Facades\Cache;

/**
 * Observer para invalidar cache de categorias.
 */
class CategoriaObserver
{
    /**
     * Invalida cache al guardar una categoria.
     *
     * @param Categoria $categoria
     * @return void
     */
    public function saved(Categoria $categoria): void
    {
        Cache::forget('categorias');
    }

    /**
     * Invalida cache al eliminar una categoria.
     *
     * @param Categoria $categoria
     * @return void
     */
    public function deleted(Categoria $categoria): void
    {
        Cache::forget('categorias');
    }
}
