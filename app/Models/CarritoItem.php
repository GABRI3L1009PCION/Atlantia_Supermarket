<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item dentro de un carrito persistido.
 *
 * @property int $id
 * @property int $cantidad
 */
class CarritoItem extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'carrito_id',
        'producto_id',
        'cantidad',
        'precio_unitario_snapshot',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario_snapshot' => 'decimal:2',
        ];
    }

    /**
     * Carrito propietario del item.
     *
     * @return BelongsTo<Carrito, CarritoItem>
     */
    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }

    /**
     * Producto seleccionado en el carrito.
     *
     * @return BelongsTo<Producto, CarritoItem>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
