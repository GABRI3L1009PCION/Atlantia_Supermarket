<?php

namespace App\Models\Ml;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recomendacion de producto para un cliente.
 *
 * @property int $id
 * @property string $algoritmo
 */
class ProductRecommendation extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cliente_id',
        'producto_id',
        'score',
        'algoritmo',
        'posicion',
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
            'score' => 'decimal:6',
            'posicion' => 'integer',
        ];
    }

    /**
     * Cliente destinatario de la recomendacion.
     *
     * @return BelongsTo<User, ProductRecommendation>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * Producto recomendado.
     *
     * @return BelongsTo<Producto, ProductRecommendation>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Version de modelo que genero la recomendacion.
     *
     * @return BelongsTo<MlModelVersion, ProductRecommendation>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra recomendaciones por algoritmo.
     *
     * @param Builder<ProductRecommendation> $query
     * @param string $algoritmo
     * @return Builder<ProductRecommendation>
     */
    public function scopeAlgoritmo(Builder $query, string $algoritmo): Builder
    {
        return $query->where('algoritmo', $algoritmo);
    }

    /**
     * Ordena recomendaciones por posicion.
     *
     * @param Builder<ProductRecommendation> $query
     * @return Builder<ProductRecommendation>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('posicion')->orderByDesc('score');
    }
}
