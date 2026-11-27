<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Repetir este pedido cada semana": el dia elegido se avisa al cliente con su pedido listo.
 */
class PedidoRecurrente extends Model
{
    public const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    protected $table = 'pedidos_recurrentes';

    protected $fillable = [
        'uuid',
        'user_id',
        'pedido_origen_id',
        'dia_semana',
        'hora_aviso',
        'activo',
        'ultimo_aviso_at',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'activo' => 'boolean',
            'ultimo_aviso_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pedidoOrigen(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_origen_id');
    }
}
