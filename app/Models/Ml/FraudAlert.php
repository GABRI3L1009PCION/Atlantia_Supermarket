<?php

namespace App\Models\Ml;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alerta antifraude generada por ML o reglas de riesgo.
 *
 * @property int $id
 * @property string $uuid
 * @property string $tipo
 */
class FraudAlert extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'pedido_id',
        'user_id',
        'tipo',
        'score_riesgo',
        'detalle',
        'revisada',
        'resuelta',
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
            'score_riesgo' => 'decimal:6',
            'detalle' => 'array',
            'revisada' => 'boolean',
            'resuelta' => 'boolean',
            'revisada_at' => 'datetime',
        ];
    }

    /**
     * Pedido relacionado con la alerta.
     *
     * @return BelongsTo<Pedido, FraudAlert>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario relacionado con la alerta.
     *
     * @return BelongsTo<User, FraudAlert>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario que reviso la alerta.
     *
     * @return BelongsTo<User, FraudAlert>
     */
    public function revisadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    /**
     * Version de modelo que genero la alerta.
     *
     * @return BelongsTo<MlModelVersion, FraudAlert>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra alertas pendientes de revision.
     *
     * @param Builder<FraudAlert> $query
     * @return Builder<FraudAlert>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('revisada', false);
    }

    /**
     * Filtra alertas resueltas.
     *
     * @param Builder<FraudAlert> $query
     * @return Builder<FraudAlert>
     */
    public function scopeResueltas(Builder $query): Builder
    {
        return $query->where('resuelta', true);
    }

    /**
     * Filtra alertas con riesgo alto.
     *
     * @param Builder<FraudAlert> $query
     * @param float $threshold
     * @return Builder<FraudAlert>
     */
    public function scopeHighRisk(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('score_riesgo', '>=', $threshold);
    }
}
