<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'ruta_planificada',
        'ruta_real',
        'distancia_km',
        'tiempo_estimado_min',
        'tiempo_real_min',
        'estado',
        'asignada_at',
        'iniciada_at',
        'completada_at',
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
            'distancia_km' => 'decimal:2',
            'tiempo_estimado_min' => 'integer',
            'tiempo_real_min' => 'integer',
            'asignada_at' => 'datetime',
            'iniciada_at' => 'datetime',
            'completada_at' => 'datetime',
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
     * Filtra rutas por estado.
     *
     * @param Builder<DeliveryRoute> $query
     * @param string $estado
     * @return Builder<DeliveryRoute>
     */
    public function scopeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Filtra rutas activas para seguimiento.
     *
     * @param Builder<DeliveryRoute> $query
     * @return Builder<DeliveryRoute>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereIn('estado', ['asignada', 'iniciada', 'pausada']);
    }

    /**
     * Filtra rutas completadas.
     *
     * @param Builder<DeliveryRoute> $query
     * @return Builder<DeliveryRoute>
     */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', 'completada');
    }
}
