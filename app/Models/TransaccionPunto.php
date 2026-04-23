<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de puntos ganado o canjeado.
 */
class TransaccionPunto extends Model
{
    use HasFactory;

    /**
     * Nombre de tabla.
     *
     * @var string
     */
    protected $table = 'transacciones_puntos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tipo',
        'puntos',
        'pedido_id',
        'descripcion',
    ];

    /**
     * Usuario asociado.
     *
     * @return BelongsTo<User, TransaccionPunto>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedido relacionado.
     *
     * @return BelongsTo<Pedido, TransaccionPunto>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
