<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ruta de entrega planificada y ejecutada para un pedido.
 *
 * @property int $id
 * @property string $uuid
 * @property string $estado
 */
class DeliveryRoute extends Model
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
        'pedido_id',
        'repartidor_id',
        'source_type',
        'pickup_name',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_notes',
        'ruta_planificada',
        'ruta_real',
        'distancia_km',
        'tiempo_estimado_min',
        'tiempo_real_min',
        'estado',
        'asignada_at',
        'aceptada_at',
        'arrived_pickup_at',
        'picked_up_at',
        'iniciada_at',
        'arrived_customer_at',
        'pickup_not_ready_at',
        'pickup_issue_reason',
        'completada_at',
        'estimated_earning',
        'tip_amount',
        'bonus_amount',
        'cash_to_collect',
        'cash_to_pay_pickup',
        'change_required',
        'payment_method',
        'proof_type',
        'confirmation_code',
        'delivered_code_confirmed_at',
        'firma_path',
        'foto_entrega_path',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ruta_planificada' => 'array',
            'ruta_real' => 'array',
            'pickup_latitude' => 'decimal:8',
            'pickup_longitude' => 'decimal:8',
            'distancia_km' => 'decimal:2',
            'tiempo_estimado_min' => 'integer',
            'tiempo_real_min' => 'integer',
            'estimated_earning' => 'decimal:2',
            'tip_amount' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'cash_to_collect' => 'decimal:2',
            'cash_to_pay_pickup' => 'decimal:2',
            'change_required' => 'decimal:2',
            'asignada_at' => 'datetime',
            'aceptada_at' => 'datetime',
            'arrived_pickup_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'iniciada_at' => 'datetime',
            'arrived_customer_at' => 'datetime',
            'pickup_not_ready_at' => 'datetime',
            'completada_at' => 'datetime',
            'delivered_code_confirmed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Pedido asociado a la ruta.
     *
     * @return BelongsTo<Pedido, DeliveryRoute>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario repartidor asignado.
     *
     * @return BelongsTo<User, DeliveryRoute>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    /**
     * Ofertas emitidas para esta ruta.
     *
     * @return HasMany<DeliveryOffer>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(DeliveryOffer::class);
    }

    /**
     * Filtra rutas por estado.
     *
     * @param  Builder<DeliveryRoute>  $query
     * @return Builder<DeliveryRoute>
     */
    public function scopeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Filtra rutas activas para seguimiento.
     *
     * @param  Builder<DeliveryRoute>  $query
     * @return Builder<DeliveryRoute>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereIn('estado', ['asignada', 'iniciada', 'pausada']);
    }

    /**
     * Filtra rutas completadas.
     *
     * @param  Builder<DeliveryRoute>  $query
     * @return Builder<DeliveryRoute>
     */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', 'completada');
    }
}
