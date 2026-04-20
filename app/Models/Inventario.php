<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventario en tiempo real de un producto.
 *
 * @property int $id
 * @property int $stock_actual
 * @property int $stock_minimo
 */
class Inventario extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'producto_id',
        'stock_actual',
        'stock_reservado',
        'stock_minimo',
        'stock_maximo',
        'ultima_actualizacion',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_actual' => 'integer',
            'stock_reservado' => 'integer',
            'stock_minimo' => 'integer',
            'stock_maximo' => 'integer',
            'ultima_actualizacion' => 'datetime',
        ];
    }

    /**
     * Producto propietario del inventario.
     *
     * @return BelongsTo<Producto, Inventario>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Filtra inventarios bajo el stock minimo.
     *
     * @param Builder<Inventario> $query
     * @return Builder<Inventario>
     */
    public function scopeBajoMinimo(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    /**
     * Filtra inventarios con stock disponible.
     *
     * @param Builder<Inventario> $query
     * @return Builder<Inventario>
     */
    public function scopeDisponible(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '>', 'stock_reservado');
    }
}
