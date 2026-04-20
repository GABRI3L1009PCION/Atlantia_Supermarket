<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categoria jerarquica del catalogo.
 *
 * @property int $id
 * @property string $nombre
 * @property string $slug
 */
class Categoria extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'nombre',
        'slug',
        'descripcion',
        'icon',
        'orden',
        'is_active',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Categoria padre.
     *
     * @return BelongsTo<Categoria, Categoria>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Categorias hijas.
     *
     * @return HasMany<Categoria>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Productos asociados a la categoria.
     *
     * @return HasMany<Producto>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Filtra categorias activas.
     *
     * @param Builder<Categoria> $query
     * @return Builder<Categoria>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra categorias raiz.
     *
     * @param Builder<Categoria> $query
     * @return Builder<Categoria>
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Ordena categorias para navegacion.
     *
     * @param Builder<Categoria> $query
     * @return Builder<Categoria>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
