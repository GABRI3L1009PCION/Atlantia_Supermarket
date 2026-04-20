<?php

namespace App\Models;

use App\Models\Ml\ReviewFlag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Resena de producto creada por un cliente verificado.
 *
 * @property int $id
 * @property string $uuid
 * @property int $calificacion
 */
class Resena extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'resenas';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'producto_id',
        'cliente_id',
        'pedido_id',
        'calificacion',
        'titulo',
        'contenido',
        'imagenes_count',
        'aprobada',
        'flagged_ml',
        'moderada_por',
        'moderada_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
            'imagenes_count' => 'integer',
            'aprobada' => 'boolean',
            'flagged_ml' => 'boolean',
            'moderada_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Producto evaluado por la resena.
     *
     * @return BelongsTo<Producto, Resena>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Cliente autor de la resena.
     *
     * @return BelongsTo<User, Resena>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * Pedido que habilita la resena verificada.
     *
     * @return BelongsTo<Pedido, Resena>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario que modero la resena.
     *
     * @return BelongsTo<User, Resena>
     */
    public function moderadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderada_por');
    }

    /**
     * Imagenes asociadas a la resena.
     *
     * @return HasMany<ResenaImagen>
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ResenaImagen::class);
    }

    /**
     * Flags generados por el modulo ML/NLP.
     *
     * @return HasMany<ReviewFlag>
     */
    public function reviewFlags(): HasMany
    {
        return $this->hasMany(ReviewFlag::class);
    }

    /**
     * Filtra resenas aprobadas.
     *
     * @param Builder<Resena> $query
     * @return Builder<Resena>
     */
    public function scopeAprobadas(Builder $query): Builder
    {
        return $query->where('aprobada', true);
    }

    /**
     * Filtra resenas pendientes de moderacion.
     *
     * @param Builder<Resena> $query
     * @return Builder<Resena>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('aprobada', false);
    }

    /**
     * Filtra resenas marcadas por ML.
     *
     * @param Builder<Resena> $query
     * @return Builder<Resena>
     */
    public function scopeFlaggedMl(Builder $query): Builder
    {
        return $query->where('flagged_ml', true);
    }
}
