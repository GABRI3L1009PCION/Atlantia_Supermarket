<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro historico de estado de pedido.
 *
 * @property int $id
 * @property string $estado
 */
class PedidoEstado extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pedido_id',
        'estado',
        'notas',
        'usuario_id',
    ];

    /**
     * Pedido asociado al cambio de estado.
     *
     * @return BelongsTo<Pedido, PedidoEstado>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario que registro el cambio.
     *
     * @return BelongsTo<User, PedidoEstado>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Filtra historial por estado.
     *
     * @param Builder<PedidoEstado> $query
     * @param string $estado
     * @return Builder<PedidoEstado>
     */
    public function scopeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }
}
