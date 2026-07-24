<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

/**
 * Politica de autorizacion para productos del marketplace.
 */
class ProductoPolicy
{
    /**
     * Permite acceso global a administradores.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar productos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['vendedor', 'empleado'])
            || $user->can('view products');
    }

    /**
     * Determina si el vendedor puede listar su catalogo propio.
     */
    public function viewOwnProducts(User $user): bool
    {
        return $user->status === 'active'
            && ($user->hasRole('vendedor') || $user->can('view own products'));
    }

    /**
     * Determina si el usuario puede ver un producto.
     */
    public function view(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            || $producto->is_active && $producto->visible_catalogo
            || $user->hasRole('empleado')
            || $user->can('view products');
    }

    /**
     * Determina si el vendedor puede crear productos.
     */
    public function create(User $user): bool
    {
        return $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('create products'));
    }

    /**
     * Determina si el usuario puede actualizar el producto.
     */
    public function update(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('update products'));
    }

    /**
     * Determina si el usuario puede eliminar el producto.
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('delete products'));
    }

    /**
     * Determina si el usuario puede ver el producto en catalogo publico.
     */
    public function viewCatalogo(?User $user, Producto $producto): bool
    {
        if ($producto->is_active && $producto->visible_catalogo && $producto->publicado_at !== null) {
            return true;
        }

        return $user !== null && ($user->isAdministrator() || $this->ownsProducto($user, $producto));
    }

    /**
     * Determina si el vendedor puede ver predicciones del producto.
     */
    public function viewDemandPrediction(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && ($user->hasRole('vendedor') || $user->can('view demand predictions'));
    }

    /**
     * Determina si el vendedor puede actualizar inventario.
     */
    public function updateInventory(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('update inventory'));
    }

    /**
     * Determina si el usuario puede moderar el producto.
     */
    public function moderate(User $user, Producto $producto): bool
    {
        return $user->hasRole('empleado') || $user->can('moderate products');
    }

    /**
     * Verifica ownership del producto por vendedor.
     */
    private function ownsProducto(User $user, Producto $producto): bool
    {
        return $user->vendor !== null && (int) $user->vendor->id === (int) $producto->vendor_id;
    }

    /**
     * Verifica que el usuario tenga vendedor aprobado.
     */
    private function hasApprovedVendor(User $user): bool
    {
        return $user->vendor !== null
            && $user->vendor->is_approved
            && $user->vendor->status === 'approved'
            && $user->status === 'active';
    }
}
