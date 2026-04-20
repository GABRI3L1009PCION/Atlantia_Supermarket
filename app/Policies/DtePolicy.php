<?php

namespace App\Policies;

use App\Models\Dte\DteFactura;
use App\Models\User;

/**
 * Politica de autorizacion para facturas electronicas DTE.
 */
class DtePolicy
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
     * Determina si el usuario puede listar DTE.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['empleado', 'vendedor'])
            || $user->can('view dtes');
    }

    /**
     * Determina si el usuario puede ver un DTE.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function view(User $user, DteFactura $dte): bool
    {
        return $this->ownsDteAsVendor($user, $dte)
            || $this->ownsDteAsCustomer($user, $dte)
            || $user->hasRole('empleado')
            || $user->can('view dtes');
    }

    /**
     * Determina si el usuario puede emitir DTE.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('vendedor')
            && $user->vendor()
                ->where('is_approved', true)
                ->where('status', 'approved')
                ->whereHas('fiscalProfile')
                ->exists();
    }

    /**
     * Determina si el usuario puede actualizar metadatos internos de un DTE.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function update(User $user, DteFactura $dte): bool
    {
        return $user->hasRole('empleado')
            && in_array($dte->estado, ['pendiente', 'rechazado'], true);
    }

    /**
     * Determina si el usuario puede eliminar un DTE.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function delete(User $user, DteFactura $dte): bool
    {
        return $user->can('delete dtes')
            && $dte->estado !== 'certificado';
    }

    /**
     * Determina si el vendedor puede listar sus DTE.
     *
     * @param User $user
     * @return bool
     */
    public function viewOwnDtes(User $user): bool
    {
        return $user->hasRole('vendedor')
            && $user->vendor()
                ->where('is_approved', true)
                ->where('status', 'approved')
                ->exists();
    }

    /**
     * Determina si el usuario puede anular un DTE certificado.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function anular(User $user, DteFactura $dte): bool
    {
        return $this->ownsDteAsVendor($user, $dte)
            && $dte->estado === 'certificado'
            && ! $dte->anulacion()->exists();
    }

    /**
     * Determina si el usuario puede descargar el XML fiscal.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function downloadXml(User $user, DteFactura $dte): bool
    {
        return $dte->estado === 'certificado'
            && (
                $this->ownsDteAsVendor($user, $dte)
                || $this->ownsDteAsCustomer($user, $dte)
                || $user->hasRole('empleado')
                || $user->can('view dtes')
            );
    }

    /**
     * Determina si el usuario puede descargar el PDF fiscal.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    public function downloadPdf(User $user, DteFactura $dte): bool
    {
        return $dte->estado === 'certificado'
            && (
                $this->ownsDteAsVendor($user, $dte)
                || $this->ownsDteAsCustomer($user, $dte)
                || $user->hasRole('empleado')
                || $user->can('view dtes')
            );
    }

    /**
     * Determina si el usuario puede ver reportes fiscales.
     *
     * @param User $user
     * @return bool
     */
    public function viewFiscalReports(User $user): bool
    {
        return $user->hasAnyRole(['empleado', 'vendedor'])
            || $user->can('view fiscal reports');
    }

    /**
     * Verifica si el DTE pertenece al vendedor autenticado.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    private function ownsDteAsVendor(User $user, DteFactura $dte): bool
    {
        if (! $user->hasRole('vendedor')) {
            return false;
        }

        return $user->vendor()
            ->whereKey($dte->vendor_id)
            ->where('is_approved', true)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Verifica si el DTE pertenece a un pedido del cliente autenticado.
     *
     * @param User $user
     * @param DteFactura $dte
     * @return bool
     */
    private function ownsDteAsCustomer(User $user, DteFactura $dte): bool
    {
        if (! $user->hasRole('cliente')) {
            return false;
        }

        $dte->loadMissing('pedido');

        return $dte->pedido?->cliente_id === $user->id;
    }
}
