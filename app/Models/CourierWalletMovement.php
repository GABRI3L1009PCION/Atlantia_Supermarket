<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento contable de billetera del repartidor.
 */
class CourierWalletMovement extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'pedido_id',
        'delivery_route_id',
        'type',
        'amount',
        'status',
        'description',
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
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Usuario repartidor.
     *
     * @return BelongsTo<User, CourierWalletMovement>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedido interno asociado.
     *
     * @return BelongsTo<Pedido, CourierWalletMovement>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Ruta asociada.
     *
     * @return BelongsTo<DeliveryRoute, CourierWalletMovement>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    /**
     * Filtra movimientos del dia.
     *
     * @param  Builder<CourierWalletMovement>  $query
     * @return Builder<CourierWalletMovement>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }
}
