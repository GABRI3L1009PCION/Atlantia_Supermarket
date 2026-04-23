<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Balance de puntos de fidelizacion del cliente.
 */
class PuntosCliente extends Model
{
    use HasFactory;

    /**
     * Nombre de tabla.
     *
     * @var string
     */
    protected $table = 'puntos_cliente';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'puntos_actuales',
        'puntos_totales_ganados',
    ];

    /**
     * Usuario propietario.
     *
     * @return BelongsTo<User, PuntosCliente>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
