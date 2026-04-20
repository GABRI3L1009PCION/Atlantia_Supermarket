<?php

namespace App\Models\Ml;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metrica diaria de desempeno de un modelo ML.
 *
 * @property int $id
 * @property string $fecha
 */
class MlMetric extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'modelo_version_id',
        'fecha',
        'mape',
        'rmse',
        'r2',
        'drift_score',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'mape' => 'decimal:4',
            'rmse' => 'decimal:4',
            'r2' => 'decimal:4',
            'drift_score' => 'decimal:4',
        ];
    }

    /**
     * Version de modelo evaluada.
     *
     * @return BelongsTo<MlModelVersion, MlMetric>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra metricas con drift superior al umbral.
     *
     * @param Builder<MlMetric> $query
     * @param float $threshold
     * @return Builder<MlMetric>
     */
    public function scopeDriftAbove(Builder $query, float $threshold): Builder
    {
        return $query->where('drift_score', '>', $threshold);
    }

    /**
     * Filtra metricas por fecha.
     *
     * @param Builder<MlMetric> $query
     * @param string $fecha
     * @return Builder<MlMetric>
     */
    public function scopeFecha(Builder $query, string $fecha): Builder
    {
        return $query->whereDate('fecha', $fecha);
    }
}
