<?php

namespace App\Models\Ml;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoria de llamadas al servicio de prediccion ML.
 *
 * @property int $id
 * @property string $endpoint
 * @property string $estado
 */
class MlPredictionLog extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'endpoint',
        'input',
        'output',
        'latencia_ms',
        'modelo_version_id',
        'estado',
        'error',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'latencia_ms' => 'integer',
        ];
    }

    /**
     * Version de modelo usada en la llamada.
     *
     * @return BelongsTo<MlModelVersion, MlPredictionLog>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra llamadas exitosas.
     *
     * @param Builder<MlPredictionLog> $query
     * @return Builder<MlPredictionLog>
     */
    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('estado', 'success');
    }

    /**
     * Filtra llamadas fallidas.
     *
     * @param Builder<MlPredictionLog> $query
     * @return Builder<MlPredictionLog>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('estado', 'failed');
    }

    /**
     * Filtra llamadas por endpoint.
     *
     * @param Builder<MlPredictionLog> $query
     * @param string $endpoint
     * @return Builder<MlPredictionLog>
     */
    public function scopeEndpoint(Builder $query, string $endpoint): Builder
    {
        return $query->where('endpoint', $endpoint);
    }
}
