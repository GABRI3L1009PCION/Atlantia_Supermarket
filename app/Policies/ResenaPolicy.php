<?php

namespace App\Policies;

use App\Models\Resena;
use App\Models\User;

/**
 * Politica de autorizacion para resenas de productos.
 */
class ResenaPolicy
{
    /**
     * Permite acceso global a administradores.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar resenas.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['empleado', 'vendedor', 'cliente'])
            || $user->can('view reviews');
    }

    /**
     * Determina si el usuario puede ver una resena.
     *
     * @param User|null $user
     * @param Resena $resena
     * @return bool
     */
    public function view(?User $user, Resena $resena): bool
    {
        if ($resena->aprobada) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $this->ownsResena($user, $resena)
            || $this->ownsReviewedProduct($user, $resena)
            || $user->hasRole('empleado')
            || $user->can('view reviews');
    }

    /**
     * Determina si el usuario puede crear resenas.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->status === 'active'
            && $user->hasRole('cliente');
    }

    /**
     * Determina si el usuario puede actualizar una resena.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    public function update(User $user, Resena $resena): bool
    {
        return $this->ownsResena($user, $resena)
            && $user->status === 'active'
            && ! $resena->aprobada;
    }

    /**
     * Determina si el usuario puede eliminar una resena.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    public function delete(User $user, Resena $resena): bool
    {
        return $this->ownsResena($user, $resena)
            || $user->hasRole('empleado')
            || $user->can('delete reviews');
    }

    /**
     * Determina si el usuario puede listar resenas de productos propios.
     *
     * @param User $user
     * @return bool
     */
    public function viewVendorReviews(User $user): bool
    {
        return $user->hasRole('vendedor')
            && $user->vendor()->where('is_approved', true)->where('status', 'approved')->exists();
    }

    /**
     * Determina si el usuario puede moderar cualquier resena.
     *
     * @param User $user
     * @return bool
     */
    public function moderateAny(User $user): bool
    {
        return $user->hasRole('empleado')
            || $user->can('moderate reviews');
    }

    /**
     * Determina si el usuario puede moderar una resena especifica.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    public function moderate(User $user, Resena $resena): bool
    {
        return ($user->hasRole('empleado') || $user->can('moderate reviews'))
            && (! $resena->aprobada || $resena->flagged_ml);
    }

    /**
     * Determina si el usuario puede revisar flags ML de una resena.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    public function reviewMlFlag(User $user, Resena $resena): bool
    {
        return ($user->hasRole('empleado') || $user->can('review ml flags'))
            && $resena->flagged_ml;
    }

    /**
     * Verifica si la resena pertenece al cliente autenticado.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    private function ownsResena(User $user, Resena $resena): bool
    {
        return $resena->cliente_id === $user->id;
    }

    /**
     * Verifica si la resena corresponde a un producto del vendedor autenticado.
     *
     * @param User $user
     * @param Resena $resena
     * @return bool
     */
    private function ownsReviewedProduct(User $user, Resena $resena): bool
    {
        if (! $user->hasRole('vendedor')) {
            return false;
        }

        $resena->loadMissing('producto');

        return $user->vendor()
            ->whereKey($resena->producto?->vendor_id)
            ->where('is_approved', true)
            ->where('status', 'approved')
            ->exists();
    }
}
