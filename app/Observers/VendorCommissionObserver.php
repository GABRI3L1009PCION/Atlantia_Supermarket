<?php

namespace App\Observers;

use App\Models\VendorCommission;
use Illuminate\Support\Facades\Cache;

/**
 * Observer para invalidar cache de comisiones activas.
 */
class VendorCommissionObserver
{
    /**
     * Invalida cache al guardar una comision.
     *
     * @param VendorCommission $vendorCommission
     * @return void
     */
    public function saved(VendorCommission $vendorCommission): void
    {
        Cache::forget("vendor_commissions:active:{$vendorCommission->vendor_id}");
    }

    /**
     * Invalida cache al eliminar una comision.
     *
     * @param VendorCommission $vendorCommission
     * @return void
     */
    public function deleted(VendorCommission $vendorCommission): void
    {
        Cache::forget("vendor_commissions:active:{$vendorCommission->vendor_id}");
    }
}
