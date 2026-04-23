<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial detallado de transiciones de un pedido.
 */
class PedidoHistorialEstado extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pedido_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'nota',
    ];

    /**
     * Pedido relacionado.
     *
     * @return BelongsTo<Pedido, PedidoHistorialEstado>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario que realizo el cambio.
     *
     * @return BelongsTo<User, PedidoHistorialEstado>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
