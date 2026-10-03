<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Oferta enviada a un repartidor para aceptar o rechazar una entrega.
 */
class DeliveryOffer extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'pedido_id',
        'external_delivery_order_id',
        'delivery_route_id',
        'repartidor_id',
        'source_type',
        'status',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'estimated_gain',
        'pickup_distance_km',
        'delivery_distance_km',
        'total_distance_km',
        'payment_method',
        'rejection_reason',
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
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'estimated_gain' => 'decimal:2',
            'pickup_distance_km' => 'decimal:2',
            'delivery_distance_km' => 'decimal:2',
            'total_distance_km' => 'decimal:2',
            'metadata' => 'array',
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
     * Pedido interno.
     *
     * @return BelongsTo<Pedido, DeliveryOffer>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Solicitud externa.
     *
     * @return BelongsTo<ExternalDeliveryOrder, DeliveryOffer>
     */
    public function externalOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalDeliveryOrder::class, 'external_delivery_order_id');
    }

    /**
     * Ruta interna.
     *
     * @return BelongsTo<DeliveryRoute, DeliveryOffer>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    /**
     * Repartidor que recibe la oferta.
     *
     * @return BelongsTo<User, DeliveryOffer>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    /**
     * Filtra ofertas pendientes aun vigentes.
     *
     * @param  Builder<DeliveryOffer>  $query
     * @return Builder<DeliveryOffer>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now());
    }
}
