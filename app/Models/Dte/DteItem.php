<?php

namespace App\Models\Dte;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item fiscal dentro de una factura DTE.
 *
 * @property int $id
 * @property string $descripcion
 */
class DteItem extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'dte_items';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dte_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'monto_iva',
        'monto_total',
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
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'monto_iva' => 'decimal:2',
            'monto_total' => 'decimal:2',
        ];
    }

    /**
     * Factura DTE propietaria del item.
     *
     * @return BelongsTo<DteFactura, DteItem>
     */
    public function dteFactura(): BelongsTo
    {
        return $this->belongsTo(DteFactura::class, 'dte_id');
    }

    /**
     * Producto asociado al item fiscal.
     *
     * @return BelongsTo<Producto, DteItem>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
