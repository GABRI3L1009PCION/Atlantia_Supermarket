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
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) || (int) $user->id === (int) $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return ! $model->isSuperAdmin() && ! $model->hasRole('admin');
        }

        return (int) $user->id === (int) $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole('admin') && ! $model->isSuperAdmin() && ! $model->hasRole('admin');
    }

    /**
     * Permite impersonar usuarios operativos desde super admin.
     */
    public function impersonate(User $user, User $model): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        return ! $model->isSuperAdmin();
    }
}
