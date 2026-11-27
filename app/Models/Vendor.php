<?php

namespace App\Models;

use App\Models\Dte\DteFactura;
use App\Support\CategoriasNegocio;
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
        'application_code',
        'user_id',
        'document_type',
        'document_number',
        'birthdate',
        'gender',
        'personal_address',
        'business_name',
        'slug',
        'descripcion',
        'business_category',
        'categorias_negocio',
        'has_nit',
        'logo_path',
        'cover_path',
        'telefono_publico',
        'email_publico',
        'municipio',
        'direccion_comercial',
        'business_address',
        'banking_info',
        'payment_frequency',
        'preferred_payment_method',
        'documents',
        'latitude',
        'longitude',
        'is_approved',
        'approved_by',
        'approved_at',
        'suspendido_at',
        'suspendido_por',
        'motivo_suspension',
        'rejection_reason',
        'status',
        'commission_percentage',
        'monthly_rent',
        'accepts_cash',
        'accepts_transfer',
        'accepts_card',
        'hora_apertura',
        'hora_cierre',
        'pausado_hasta',
        'pausa_motivo',
        'pagos_retenidos_at',
        'motivo_retencion',
        'revision_automatica',
        'tiempo_preparacion_min',
        'pedido_minimo',
        'alerta_sonora_pedidos',
        'plan',
        'plan_hasta',
        'plan_suscripcion_id',
        'limite_productos',
        'prueba_plan',
        'prueba_hasta',
        'prueba_usada_at',
        'prueba_aviso_previo_at',
        'prueba_aviso_fin_at',
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
            'birthdate' => 'date',
            'personal_address' => 'array',
            'has_nit' => 'boolean',
            'business_address' => 'array',
            'banking_info' => 'array',
            'categorias_negocio' => 'array',
            'documents' => 'array',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'suspendido_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'truth_accepted_at' => 'datetime',
            'data_consent_at' => 'datetime',
            'commission_percentage' => 'decimal:2',
            'monthly_rent' => 'decimal:2',
            'accepts_cash' => 'boolean',
            'pausado_hasta' => 'datetime',
            'pagos_retenidos_at' => 'datetime',
            'revision_automatica' => 'array',
            'tiempo_preparacion_min' => 'integer',
            'pedido_minimo' => 'decimal:2',
            'alerta_sonora_pedidos' => 'boolean',
            'plan_hasta' => 'datetime',
            'prueba_hasta' => 'datetime',
            'prueba_usada_at' => 'datetime',
            'prueba_aviso_previo_at' => 'datetime',
            'prueba_aviso_fin_at' => 'datetime',
            'accepts_transfer' => 'boolean',
            'accepts_card' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Denuncias de clientes contra la tienda.
     *
     * @return HasMany<DenunciaTienda>
     */
    public function denuncias(): HasMany
    {
        return $this->hasMany(DenunciaTienda::class);
    }

    /**
     * Está usando su prueba gratis de la promoción de apertura.
     */
    public function enPrueba(): bool
    {
        return $this->prueba_hasta !== null && $this->prueba_hasta->isFuture();
    }

    /**
     * Comisión que se cobra hoy por cada venta: durante la prueba gratis no se cobra.
     */
    public function comisionVigente(): float
    {
        return $this->enPrueba() ? 0.0 : (float) $this->commission_percentage;
    }

    /**
     * Etiquetas de las categorías de la tienda (hasta 3). Si no eligió ninguna, usa la categoría antigua.
     *
     * @return array<int, string>
     */
    public function categoriasEtiquetas(): array
    {
        $claves = array_values(array_filter((array) ($this->categorias_negocio ?? [])));

        if ($claves === []) {
            $antigua = CategoriasNegocio::etiqueta($this->business_category);

            return $antigua === null ? [] : [$antigua];
        }

        return array_values(array_filter(array_map(
            static fn (string $clave): ?string => CategoriasNegocio::etiqueta($clave),
            $claves,
        )));
    }

    /**
     * Categorías listas para mostrar: "Belleza y cuidado personal · Abarrotes y supermercado".
     */
    public function categoriaTexto(): ?string
    {
        $etiquetas = $this->categoriasEtiquetas();

        return $etiquetas === [] ? null : implode(' · ', $etiquetas);
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
     * @param  Builder<Vendor>  $query
     * @return Builder<Vendor>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true)->where('status', 'approved');
    }

    /**
     * Filtra vendedores pendientes de aprobacion.
     *
     * @param  Builder<Vendor>  $query
     * @return Builder<Vendor>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filtra vendedores suspendidos.
     *
     * @param  Builder<Vendor>  $query
     * @return Builder<Vendor>
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Filtra vendedores por municipio.
     *
     * @param  Builder<Vendor>  $query
     * @return Builder<Vendor>
     */
    public function scopeMunicipio(Builder $query, string $municipio): Builder
    {
        return $query->where('municipio', $municipio);
    }
}
