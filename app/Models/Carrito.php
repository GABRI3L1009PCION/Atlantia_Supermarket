<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carrito persistido de cliente o visitante.
 *
 * @property int $id
 * @property string $uuid
 * @property string $estado
 */
class Carrito extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'estado',
        'expira_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expira_at' => 'datetime',
        ];
    }

    /**
     * Usuario propietario del carrito autenticado.
     *
     * @return BelongsTo<User, Carrito>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items del carrito.
     *
     * @return HasMany<CarritoItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CarritoItem::class);
    }

    /**
     * Filtra carritos activos.
     *
     * @param Builder<Carrito> $query
     * @return Builder<Carrito>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Filtra carritos expirados.
     *
     * @param Builder<Carrito> $query
     * @return Builder<Carrito>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('estado', 'expirado');
    }
}
