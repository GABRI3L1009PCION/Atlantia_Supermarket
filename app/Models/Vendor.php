<?php

namespace App\Models;

use App\Models\Dte\DteFactura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendedor local afiliado al marketplace Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property string $business_name
 * @property string $status
 */
class Vendor extends Model
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
        'user_id',
        'business_name',
        'slug',
        'descripcion',
        'logo_path',
        'cover_path',
        'telefono_publico',
        'email_publico',
        'municipio',
        'direccion_comercial',
        'latitude',
        'longitude',
        'is_approved',
        'approved_by',
        'approved_at',
        'suspendido_at',
        'suspendido_por',
        'motivo_suspension',
        'status',
        'commission_percentage',
        'monthly_rent',
        'accepts_cash',
        'accepts_transfer',
        'accepts_card',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'suspendido_at' => 'datetime',
            'commission_percentage' => 'decimal:2',
            'monthly_rent' => 'decimal:2',
            'accepts_cash' => 'boolean',
            'accepts_transfer' => 'boolean',
            'accepts_card' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usuario propietario del vendedor.
     *
     * @return BelongsTo<User, Vendor>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario administrador que aprobo el vendedor.
     *
     * @return BelongsTo<User, Vendor>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Usuario administrador que suspendio el vendedor.
     *
     * @return BelongsTo<User, Vendor>
     */
    public function suspendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspendido_por');
    }

    /**
     * Perfil fiscal FEL del vendedor.
     *
     * @return HasOne<VendorFiscalProfile>
     */
    public function fiscalProfile(): HasOne
    {
        return $this->hasOne(VendorFiscalProfile::class);
    }

    /**
     * Productos publicados por el vendedor.
     *
     * @return HasMany<Producto>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Pedidos recibidos por el vendedor.
     *
     * @return HasMany<Pedido>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Pagos divididos asignados al vendedor.
     *
     * @return HasMany<PaymentSplit>
     */
    public function paymentSplits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class);
    }

    /**
     * Zonas de entrega configuradas para el vendedor.
     *
     * @return HasMany<VendorDeliveryZone>
     */
    public function vendorDeliveryZones(): HasMany
    {
        return $this->hasMany(VendorDeliveryZone::class);
    }

    /**
     * Zonas globales donde el vendedor entrega.
     *
     * @return BelongsToMany<DeliveryZone>
     */
    public function deliveryZones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'vendor_delivery_zones')
            ->withPivot(['costo_override', 'tiempo_estimado_min', 'activa'])
            ->withTimestamps();
    }

    /**
     * Facturas FEL emitidas por el vendedor.
     *
     * @return HasMany<DteFactura>
     */
    public function dteFacturas(): HasMany
    {
        return $this->hasMany(DteFactura::class);
    }

    /**
     * Comisiones mensuales del vendedor.
     *
     * @return HasMany<VendorCommission>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(VendorCommission::class);
    }

    /**
     * Filtra vendedores aprobados.
     *
     * @param Builder<Vendor> $query
     * @return Builder<Vendor>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true)->where('status', 'approved');
    }

    /**
     * Filtra vendedores pendientes de aprobacion.
     *
     * @param Builder<Vendor> $query
     * @return Builder<Vendor>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filtra vendedores suspendidos.
     *
     * @param Builder<Vendor> $query
     * @return Builder<Vendor>
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Filtra vendedores por municipio.
     *
     * @param Builder<Vendor> $query
     * @param string $municipio
     * @return Builder<Vendor>
     */
    public function scopeMunicipio(Builder $query, string $municipio): Builder
    {
        return $query->where('municipio', $municipio);
    }
}
