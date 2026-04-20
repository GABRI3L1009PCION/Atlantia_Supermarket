<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Imagen adjunta a una resena.
 *
 * @property int $id
 * @property string $path
 */
class ResenaImagen extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'resena_imagenes';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'resena_id',
        'path',
        'orden',
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
        ];
    }

    /**
     * Resena propietaria de la imagen.
     *
     * @return BelongsTo<Resena, ResenaImagen>
     */
    public function resena(): BelongsTo
    {
        return $this->belongsTo(Resena::class);
    }

    /**
     * Ordena imagenes por posicion visual.
     *
     * @param Builder<ResenaImagen> $query
     * @return Builder<ResenaImagen>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('id');
    }
}
