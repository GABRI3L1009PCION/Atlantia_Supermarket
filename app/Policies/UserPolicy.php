<?php

namespace App\Policies;

use App\Models\User;

/**
 * Politica de usuarios.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin') || (int) $user->id === (int) $model->id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin') || (int) $user->id === (int) $model->id;
    }
}

