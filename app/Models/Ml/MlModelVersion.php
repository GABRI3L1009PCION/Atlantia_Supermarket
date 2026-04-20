<?php

namespace App\Models\Ml;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Version registrada de un modelo ML.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nombre_modelo
 * @property string $estado
 */
class MlModelVersion extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nombre_modelo',
        'version',
        'ruta_artefacto',
        'metricas',
        'fecha_entrenamiento',
        'fecha_deploy',
        'estado',
        'entrenado_por',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metricas' => 'array',
            'fecha_entrenamiento' => 'datetime',
            'fecha_deploy' => 'datetime',
        ];
    }

    /**
     * Usuario que entreno o registro el modelo.
     *
     * @return BelongsTo<User, MlModelVersion>
     */
    public function entrenadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrenado_por');
    }

    /**
     * Jobs de entrenamiento asociados.
     *
     * @return HasMany<MlTrainingJob>
     */
    public function trainingJobs(): HasMany
    {
        return $this->hasMany(MlTrainingJob::class, 'modelo_version_id');
    }

    /**
     * Predicciones de venta generadas.
     *
     * @return HasMany<SalesPrediction>
     */
    public function salesPredictions(): HasMany
    {
        return $this->hasMany(SalesPrediction::class, 'modelo_version_id');
    }

    /**
     * Logs de prediccion asociados.
     *
     * @return HasMany<MlPredictionLog>
     */
    public function predictionLogs(): HasMany
    {
        return $this->hasMany(MlPredictionLog::class, 'modelo_version_id');
    }

    /**
     * Metricas diarias del modelo.
     *
     * @return HasMany<MlMetric>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(MlMetric::class, 'modelo_version_id');
    }

    /**
     * Filtra versiones en produccion.
     *
     * @param Builder<MlModelVersion> $query
     * @return Builder<MlModelVersion>
     */
    public function scopeProduction(Builder $query): Builder
    {
        return $query->where('estado', 'production');
    }

    /**
     * Filtra versiones en staging.
     *
     * @param Builder<MlModelVersion> $query
     * @return Builder<MlModelVersion>
     */
    public function scopeStaging(Builder $query): Builder
    {
        return $query->where('estado', 'staging');
    }

    /**
     * Filtra versiones por nombre de modelo.
     *
     * @param Builder<MlModelVersion> $query
     * @param string $nombreModelo
     * @return Builder<MlModelVersion>
     */
    public function scopeNombreModelo(Builder $query, string $nombreModelo): Builder
    {
        return $query->where('nombre_modelo', $nombreModelo);
    }
}
