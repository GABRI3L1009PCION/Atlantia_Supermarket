<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linea de producto dentro de un pedido.
 *
 * @property int $id
 * @property int $cantidad
 */
class PedidoItem extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'producto_nombre_snapshot',
        'producto_sku_snapshot',
        'cantidad',
        'precio_unitario_snapshot',
        'subtotal',
        'descuento',
        'impuestos',
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
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'impuestos' => 'decimal:2',
        ];
    }

    /**
     * Pedido propietario del item.
     *
     * @return BelongsTo<Pedido, PedidoItem>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Producto original del item.
     *
     * @return BelongsTo<Producto, PedidoItem>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
