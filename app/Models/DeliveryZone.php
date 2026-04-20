<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Zona global de entrega configurada por Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nombre
 */
class DeliveryZone extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nombre',
        'slug',
        'descripcion',
        'municipio',
        'costo_base',
        'latitude_centro',
        'longitude_centro',
        'poligono_geojson',
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
            'costo_base' => 'decimal:2',
            'latitude_centro' => 'decimal:8',
            'longitude_centro' => 'decimal:8',
            'poligono_geojson' => 'array',
            'activa' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Configuraciones de vendedores para esta zona.
     *
     * @return HasMany<VendorDeliveryZone>
     */
    public function vendorDeliveryZones(): HasMany
    {
        return $this->hasMany(VendorDeliveryZone::class);
    }

    /**
     * Vendedores que entregan en esta zona.
     *
     * @return BelongsToMany<Vendor>
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_delivery_zones')
            ->withPivot(['costo_override', 'tiempo_estimado_min', 'activa'])
            ->withTimestamps();
    }

    /**
     * Filtra zonas activas.
     *
     * @param Builder<DeliveryZone> $query
     * @return Builder<DeliveryZone>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Filtra zonas por municipio.
     *
     * @param Builder<DeliveryZone> $query
     * @param string $municipio
     * @return Builder<DeliveryZone>
     */
    public function scopeMunicipio(Builder $query, string $municipio): Builder
    {
        return $query->where('municipio', $municipio);
    }
}
