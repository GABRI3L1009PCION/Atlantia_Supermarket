<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

/**
 * Politica de autorizacion para vendedores locales.
 */
class VendorPolicy
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
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar vendedores.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['empleado', 'vendedor'])
            || $user->can('view vendors');
    }

    /**
     * Determina si el usuario puede ver un vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $this->ownsVendor($user, $vendor)
            || $user->hasRole('empleado')
            || $user->can('view vendors');
    }

    /**
     * Determina si el usuario puede solicitar perfil de vendedor.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->status === 'active'
            && ! $user->vendor()->exists()
            && $user->hasAnyRole(['cliente', 'vendedor']);
    }

    /**
     * Determina si el usuario puede actualizar un vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $this->ownsVendor($user, $vendor)
            && $user->status === 'active'
            && $vendor->status !== 'suspended';
    }

    /**
     * Determina si el usuario puede eliminar un vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('delete vendors');
    }

    /**
     * Determina si el usuario puede aprobar solicitudes de vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function approve(User $user, Vendor $vendor): bool
    {
        return $user->can('approve vendors')
            && $vendor->status === 'pending'
            && ! $vendor->is_approved;
    }

    /**
     * Determina si el usuario puede suspender un vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function suspend(User $user, Vendor $vendor): bool
    {
        return $user->can('suspend vendors')
            && $vendor->status !== 'suspended'
            && $vendor->is_approved;
    }

    /**
     * Determina si el usuario puede reactivar un vendedor suspendido.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function reactivate(User $user, Vendor $vendor): bool
    {
        return $user->can('approve vendors')
            && $vendor->status === 'suspended';
    }

    /**
     * Determina si el usuario puede administrar el perfil fiscal del vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function manageFiscalProfile(User $user, Vendor $vendor): bool
    {
        return $this->ownsVendor($user, $vendor)
            && $user->status === 'active'
            && $vendor->status !== 'suspended';
    }

    /**
     * Determina si el usuario puede administrar zonas de entrega del vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function manageDeliveryZones(User $user, Vendor $vendor): bool
    {
        return $this->ownsVendor($user, $vendor)
            && $vendor->is_approved
            && $vendor->status === 'approved';
    }

    /**
     * Determina si el usuario puede ver metricas del vendedor.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    public function viewMetrics(User $user, Vendor $vendor): bool
    {
        return $this->ownsVendor($user, $vendor)
            || $user->hasRole('empleado')
            || $user->can('view reports');
    }

    /**
     * Verifica si el vendedor pertenece al usuario autenticado.
     *
     * @param User $user
     * @param Vendor $vendor
     * @return bool
     */
    private function ownsVendor(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id;
    }
}
