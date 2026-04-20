<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuracion de zona de entrega para un vendedor.
 *
 * @property int $id
 * @property bool $activa
 */
class VendorDeliveryZone extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_id',
        'delivery_zone_id',
        'costo_override',
        'tiempo_estimado_min',
        'activa',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'costo_override' => 'decimal:2',
            'tiempo_estimado_min' => 'integer',
            'activa' => 'boolean',
        ];
    }

    /**
     * Vendedor asociado a la zona.
     *
     * @return BelongsTo<Vendor, VendorDeliveryZone>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Zona global de entrega.
     *
     * @return BelongsTo<DeliveryZone, VendorDeliveryZone>
     */
    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * Filtra relaciones activas.
     *
     * @param Builder<VendorDeliveryZone> $query
     * @return Builder<VendorDeliveryZone>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activa', true);
    }
}
