<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mensaje enviado por clientes o visitantes al equipo Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property bool $atendido
 */
class ContactMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'nombre',
        'email',
        'telefono',
        'asunto',
        'mensaje',
        'atendido',
        'atendido_por',
        'atendido_at',
        'prioridad',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'atendido' => 'boolean',
            'atendido_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usuario autenticado que envio el mensaje, si aplica.
     *
     * @return BelongsTo<User, ContactMessage>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario interno que atendio el mensaje.
     *
     * @return BelongsTo<User, ContactMessage>
     */
    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    /**
     * Filtra mensajes pendientes de atencion.
     *
     * @param Builder<ContactMessage> $query
     * @return Builder<ContactMessage>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('atendido', false);
    }

    /**
     * Filtra mensajes atendidos.
     *
     * @param Builder<ContactMessage> $query
     * @return Builder<ContactMessage>
     */
    public function scopeAtendidos(Builder $query): Builder
    {
        return $query->where('atendido', true);
    }

    /**
     * Filtra mensajes por prioridad.
     *
     * @param Builder<ContactMessage> $query
     * @param string $prioridad
     * @return Builder<ContactMessage>
     */
    public function scopePrioridad(Builder $query, string $prioridad): Builder
    {
        return $query->where('prioridad', $prioridad);
    }
}
