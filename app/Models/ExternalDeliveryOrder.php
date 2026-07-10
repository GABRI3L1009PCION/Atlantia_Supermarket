<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Solicitud de entrega creada por una tienda externa en linea.
 */
class ExternalDeliveryOrder extends Model
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
        'source_channel',
        'external_reference',
        'store_name',
        'store_contact_name',
        'store_phone',
        'store_email',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_notes',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_notes',
        'payment_method',
        'amount_to_collect',
        'amount_to_pay_store',
        'change_required',
        'delivery_fee',
        'courier_earning',
        'tip_amount',
        'estimated_distance_km',
        'estimated_time_min',
        'status',
        'repartidor_id',
        'requested_at',
        'offered_at',
        'assigned_at',
        'accepted_at',
        'arrived_pickup_at',
        'picked_up_at',
        'arrived_customer_at',
        'delivered_at',
        'cancelled_at',
        'pickup_not_ready_at',
        'pickup_issue_reason',
        'proof_photo_path',
        'confirmation_code',
        'confirmation_code_verified_at',
        'cash_issue_reported_at',
        'cash_issue_notes',
        'metadata',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:8',
            'pickup_longitude' => 'decimal:8',
            'delivery_latitude' => 'decimal:8',
            'delivery_longitude' => 'decimal:8',
            'amount_to_collect' => 'decimal:2',
            'amount_to_pay_store' => 'decimal:2',
            'change_required' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'courier_earning' => 'decimal:2',
            'tip_amount' => 'decimal:2',
            'estimated_distance_km' => 'decimal:2',
            'estimated_time_min' => 'integer',
            'requested_at' => 'datetime',
            'offered_at' => 'datetime',
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'arrived_pickup_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'arrived_customer_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'pickup_not_ready_at' => 'datetime',
            'confirmation_code_verified_at' => 'datetime',
            'cash_issue_reported_at' => 'datetime',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usa UUID para rutas publicas.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Repartidor asignado.
     *
     * @return BelongsTo<User, ExternalDeliveryOrder>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    /**
     * Ofertas emitidas para esta solicitud.
     *
     * @return HasMany<DeliveryOffer>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(DeliveryOffer::class);
    }

    /**
     * Filtra solicitudes activas.
     *
     * @param  Builder<ExternalDeliveryOrder>  $query
     * @return Builder<ExternalDeliveryOrder>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }
}
