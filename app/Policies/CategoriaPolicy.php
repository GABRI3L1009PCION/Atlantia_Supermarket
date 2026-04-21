<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

/**
 * Politica para categorias del catalogo.
 */
class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
