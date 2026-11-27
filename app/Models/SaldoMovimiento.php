<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento del saldo a favor (monedero) de un cliente.
 */
class SaldoMovimiento extends Model
{
    protected $table = 'saldo_movimientos';

    protected $fillable = [
        'user_id',
        'pedido_id',
        'monto',
        'motivo',
        'descripcion',
        'creado_por',
    ];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
