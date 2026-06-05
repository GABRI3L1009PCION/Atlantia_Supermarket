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
        foreach (['fel_llave_firma', 'fel_llave_certificador'] as $secretField) {
            if (array_key_exists($secretField, $data) && blank($data[$secretField])) {
                unset($data[$secretField]);
            }
        }

        return $user->vendor->fiscalProfile()->updateOrCreate(['vendor_id' => $user->vendor->id], $data);
    }
}
