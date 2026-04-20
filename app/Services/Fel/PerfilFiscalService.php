<?php

namespace App\Services\Fel;

use App\Models\User;
use App\Models\VendorFiscalProfile;

/**
 * Servicio de perfil fiscal FEL del vendedor.
 */
class PerfilFiscalService
{
    /**
     * Devuelve perfil fiscal del vendedor.
     */
    public function detail(User $user): ?VendorFiscalProfile
    {
        return $user->vendor?->fiscalProfile;
    }

    /**
     * Actualiza perfil fiscal.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): VendorFiscalProfile
    {
        return $user->vendor->fiscalProfile()->updateOrCreate(['vendor_id' => $user->vendor->id], $data);
    }
}

