<?php

namespace App\Models\Ml;

use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prediccion de ventas por producto y horizonte.
 *
 * @property int $id
 * @property int $horizonte_dias
 */
class SalesPrediction extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'producto_id',
        'vendor_id',
        'fecha_prediccion',
        'horizonte_dias',
        'valor_predicho',
        'valor_real',
        'intervalo_inferior',
        'intervalo_superior',
        'modelo_version_id',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_prediccion' => 'date',
            'horizonte_dias' => 'integer',
            'valor_predicho' => 'decimal:2',
            'valor_real' => 'decimal:2',
            'intervalo_inferior' => 'decimal:2',
            'intervalo_superior' => 'decimal:2',
        ];
    }

    /**
     * Producto al que pertenece la prediccion.
     *
     * @return BelongsTo<Producto, SalesPrediction>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Vendedor propietario del producto.
     *
     * @return BelongsTo<Vendor, SalesPrediction>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Version de modelo que genero la prediccion.
     *
     * @return BelongsTo<MlModelVersion, SalesPrediction>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra predicciones por horizonte.
     *
     * @param Builder<SalesPrediction> $query
     * @param int $dias
     * @return Builder<SalesPrediction>
     */
    public function scopeHorizonte(Builder $query, int $dias): Builder
    {
        return $query->where('horizonte_dias', $dias);
    }

    /**
     * Filtra predicciones de una fecha.
     *
     * @param Builder<SalesPrediction> $query
     * @param string $fecha
     * @return Builder<SalesPrediction>
     */
    public function scopeFecha(Builder $query, string $fecha): Builder
    {
        return $query->whereDate('fecha_prediccion', $fecha);
    }
}
