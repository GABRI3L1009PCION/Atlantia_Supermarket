<?php

namespace App\Models\Dte;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anulacion fiscal de una factura DTE.
 *
 * @property int $id
 * @property string $uuid
 * @property string $estado
 */
class DteAnulacion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'dte_anulaciones';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'dte_id',
        'motivo',
        'fecha_anulacion',
        'usuario_id',
        'uuid_anulacion_sat',
        'estado',
        'certificador_respuesta',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_anulacion' => 'datetime',
            'certificador_respuesta' => 'array',
        ];
    }

    /**
     * Factura DTE anulada.
     *
     * @return BelongsTo<DteFactura, DteAnulacion>
     */
    public function dteFactura(): BelongsTo
    {
        return $this->belongsTo(DteFactura::class, 'dte_id');
    }

    /**
     * Usuario que solicito o registro la anulacion.
     *
     * @return BelongsTo<User, DteAnulacion>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Filtra anulaciones aceptadas.
     *
     * @param Builder<DteAnulacion> $query
     * @return Builder<DteAnulacion>
     */
    public function scopeAceptadas(Builder $query): Builder
    {
        return $query->where('estado', 'aceptada');
    }

    /**
     * Filtra anulaciones rechazadas.
     *
     * @param Builder<DteAnulacion> $query
     * @return Builder<DteAnulacion>
     */
    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('estado', 'rechazada');
    }
}
