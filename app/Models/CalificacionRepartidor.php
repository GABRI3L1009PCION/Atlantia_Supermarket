<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Calificación que deja el cliente al repartidor que le entregó un pedido.
 */
class CalificacionRepartidor extends Model
{
    protected $table = 'calificaciones_repartidor';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['pedido_id', 'repartidor_id', 'cliente_id', 'estrellas', 'comentario'];

    protected function casts(): array
    {
        return ['estrellas' => 'integer'];
    }

    /**
     * @return BelongsTo<Pedido, CalificacionRepartidor>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return BelongsTo<User, CalificacionRepartidor>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }
}
