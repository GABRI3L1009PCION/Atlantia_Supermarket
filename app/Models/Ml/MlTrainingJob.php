<?php

namespace App\Models\Ml;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Job de entrenamiento ejecutado por el microservicio ML.
 *
 * @property int $id
 * @property string $uuid
 * @property string $estado
 */
class MlTrainingJob extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'modelo_nombre',
        'modelo_version_id',
        'inicio_at',
        'fin_at',
        'estado',
        'metricas_finales',
        'dataset_size',
        'error_log',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inicio_at' => 'datetime',
            'fin_at' => 'datetime',
            'metricas_finales' => 'array',
            'dataset_size' => 'integer',
        ];
    }

    /**
     * Version de modelo generada o usada por el job.
     *
     * @return BelongsTo<MlModelVersion, MlTrainingJob>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra jobs completados.
     *
     * @param Builder<MlTrainingJob> $query
     * @return Builder<MlTrainingJob>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('estado', 'completed');
    }

    /**
     * Filtra jobs fallidos.
     *
     * @param Builder<MlTrainingJob> $query
     * @return Builder<MlTrainingJob>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('estado', 'failed');
    }

    /**
     * Filtra jobs activos.
     *
     * @param Builder<MlTrainingJob> $query
     * @return Builder<MlTrainingJob>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('estado', ['queued', 'running']);
    }
}
