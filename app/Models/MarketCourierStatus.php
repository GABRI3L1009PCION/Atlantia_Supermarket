<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado GPS reportado por un repartidor.
 *
 * @property int $id
 * @property string $estado
 */
class MarketCourierStatus extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'repartidor_id',
        'pedido_id',
        'latitude',
        'longitude',
        'timestamp_gps',
        'estado',
        'battery_level',
        'accuracy_meters',
        'notas',
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
            'timestamp_gps' => 'datetime',
            'battery_level' => 'integer',
            'accuracy_meters' => 'decimal:2',
        ];
    }

    /**
     * Usuario repartidor que reporto el estado.
     *
     * @return BelongsTo<User, MarketCourierStatus>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    /**
     * Pedido asociado al estado GPS.
     *
     * @return BelongsTo<Pedido, MarketCourierStatus>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Filtra estados por repartidor.
     *
     * @param Builder<MarketCourierStatus> $query
     * @param int $repartidorId
     * @return Builder<MarketCourierStatus>
     */
    public function scopeForRepartidor(Builder $query, int $repartidorId): Builder
    {
        return $query->where('repartidor_id', $repartidorId);
    }

    /**
     * Filtra estados por pedido.
     *
     * @param Builder<MarketCourierStatus> $query
     * @param int $pedidoId
     * @return Builder<MarketCourierStatus>
     */
    public function scopeForPedido(Builder $query, int $pedidoId): Builder
    {
        return $query->where('pedido_id', $pedidoId);
    }

    /**
     * Ordena estados desde el mas reciente.
     *
     * @param Builder<MarketCourierStatus> $query
     * @return Builder<MarketCourierStatus>
     */
    public function scopeLatestGps(Builder $query): Builder
    {
        return $query->orderByDesc('timestamp_gps');
    }
}
