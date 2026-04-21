<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'path',
        'alt_text',
        'orden',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    /**
     * @return BelongsTo<Producto, ProductoImagen>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @param Builder<ProductoImagen> $query
     * @return Builder<ProductoImagen>
     */
    public function scopePrincipales(Builder $query): Builder
    {
        return $query->where('es_principal', true);
    }

    /**
     * @param Builder<ProductoImagen> $query
     * @return Builder<ProductoImagen>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
