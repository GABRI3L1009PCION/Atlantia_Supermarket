<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de uso de cupon por cliente.
 */
class CuponUso extends Model
{
    use HasFactory;

    /**
     * Nombre de tabla.
     *
     * @var string
     */
    protected $table = 'cupon_uso';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cupon_id',
        'user_id',
        'pedido_id',
    ];

    /**
     * Cupon relacionado.
     *
     * @return BelongsTo<Cupon, CuponUso>
     */
    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class);
    }

    /**
     * Usuario que aplico el cupon.
     *
     * @return BelongsTo<User, CuponUso>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedido relacionado.
     *
     * @return BelongsTo<Pedido, CuponUso>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
