<?php

namespace App\Models\Cliente;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detalle extendido del perfil de cliente.
 *
 * @property int $id
 * @property int $user_id
 */
class ClienteDetalle extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'cliente_detalles';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'dpi',
        'telefono',
        'fecha_nacimiento',
        'genero',
        'preferencias',
        'acepta_marketing',
        'terminos_aceptados_at',
        'privacidad_aceptada_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dpi' => 'encrypted',
            'telefono' => 'encrypted',
            'fecha_nacimiento' => 'date',
            'preferencias' => 'array',
            'acepta_marketing' => 'boolean',
            'terminos_aceptados_at' => 'datetime',
            'privacidad_aceptada_at' => 'datetime',
        ];
    }

    /**
     * Usuario propietario del detalle de cliente.
     *
     * @return BelongsTo<User, ClienteDetalle>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filtra clientes que aceptan comunicaciones de marketing.
     *
     * @param Builder<ClienteDetalle> $query
     * @return Builder<ClienteDetalle>
     */
    public function scopeAceptaMarketing(Builder $query): Builder
    {
        return $query->where('acepta_marketing', true);
    }
}
