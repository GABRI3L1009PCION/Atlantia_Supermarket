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
     * Determina si el usuario puede listar productos.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['vendedor', 'empleado'])
            || $user->can('view products');
    }

    /**
     * Determina si el usuario puede ver un producto.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
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
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('create products'));
    }

    /**
     * Determina si el usuario puede actualizar el producto.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    public function update(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('update products'));
    }

    /**
     * Determina si el usuario puede eliminar el producto.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('delete products'));
    }

    /**
     * Determina si el usuario puede ver el producto en catalogo publico.
     *
     * @param User|null $user
     * @param Producto $producto
     * @return bool
     */
    public function viewCatalogo(?User $user, Producto $producto): bool
    {
        if ($producto->is_active && $producto->visible_catalogo && $producto->publicado_at !== null) {
            return true;
        }

        return $user !== null && ($user->hasRole('admin') || $this->ownsProducto($user, $producto));
    }

    /**
     * Determina si el vendedor puede ver predicciones del producto.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    public function viewDemandPrediction(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && ($user->hasRole('vendedor') || $user->can('view demand predictions'));
    }

    /**
     * Determina si el vendedor puede actualizar inventario.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    public function updateInventory(User $user, Producto $producto): bool
    {
        return $this->ownsProducto($user, $producto)
            && $this->hasApprovedVendor($user)
            && ($user->hasRole('vendedor') || $user->can('update inventory'));
    }

    /**
     * Determina si el usuario puede moderar el producto.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    public function moderate(User $user, Producto $producto): bool
    {
        return $user->hasRole('empleado') || $user->can('moderate products');
    }

    /**
     * Verifica ownership del producto por vendedor.
     *
     * @param User $user
     * @param Producto $producto
     * @return bool
     */
    private function ownsProducto(User $user, Producto $producto): bool
    {
        return $user->vendor !== null && (int) $user->vendor->id === (int) $producto->vendor_id;
    }

    /**
     * Verifica que el usuario tenga vendedor aprobado.
     *
     * @param User $user
     * @return bool
     */
    private function hasApprovedVendor(User $user): bool
    {
        return $user->vendor !== null
            && $user->vendor->is_approved
            && $user->vendor->status === 'approved'
            && $user->status === 'active';
    }
}
