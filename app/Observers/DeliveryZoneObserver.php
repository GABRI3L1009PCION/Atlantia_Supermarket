<?php

namespace App\Observers;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Cache;

/**
 * Observer para invalidar cache operativo de zonas.
 */
class DeliveryZoneObserver
{
    /**
     * Invalida cache al guardar una zona.
     */
    public function saved(DeliveryZone $deliveryZone): void
    {
        Cache::forget('delivery_zones:active');
    }

    /**
     * Invalida cache al eliminar una zona.
     */
    public function deleted(DeliveryZone $deliveryZone): void
    {
        Cache::forget('delivery_zones:active');
    }
}
