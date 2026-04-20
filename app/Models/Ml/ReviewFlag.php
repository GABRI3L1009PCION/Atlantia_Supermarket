<?php

namespace App\Models\Ml;

use App\Models\Resena;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marcador ML para moderacion de resenas.
 *
 * @property int $id
 * @property string $razon_ml
 */
class ReviewFlag extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'resena_id',
        'razon_ml',
        'score_sospecha',
        'revisada',
        'accion_tomada',
        'revisada_por',
        'revisada_at',
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
            'score_sospecha' => 'decimal:6',
            'revisada' => 'boolean',
            'revisada_at' => 'datetime',
        ];
    }

    /**
     * Resena marcada por el analisis ML.
     *
     * @return BelongsTo<Resena, ReviewFlag>
     */
    public function resena(): BelongsTo
    {
        return $this->belongsTo(Resena::class);
    }

    /**
     * Usuario que reviso el flag.
     *
     * @return BelongsTo<User, ReviewFlag>
     */
    public function revisadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    /**
     * Version de modelo que genero el flag.
     *
     * @return BelongsTo<MlModelVersion, ReviewFlag>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra flags pendientes.
     *
     * @param Builder<ReviewFlag> $query
     * @return Builder<ReviewFlag>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('revisada', false);
    }

    /**
     * Filtra flags con sospecha alta.
     *
     * @param Builder<ReviewFlag> $query
     * @param float $threshold
     * @return Builder<ReviewFlag>
     */
    public function scopeHighSuspicion(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('score_sospecha', '>=', $threshold);
    }
}
