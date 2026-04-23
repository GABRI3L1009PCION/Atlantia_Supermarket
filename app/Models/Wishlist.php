<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto guardado por el cliente en su lista de deseos.
 */
class Wishlist extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'producto_id',
    ];

    /**
     * Cliente propietario.
     *
     * @return BelongsTo<User, Wishlist>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Producto guardado.
     *
     * @return BelongsTo<Producto, Wishlist>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
