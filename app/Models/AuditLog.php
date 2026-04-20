<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro inmutable de auditoria del sistema.
 *
 * @property int $id
 * @property string $uuid
 * @property string $event
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'request_id',
        'url',
        'method',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Usuario que origino el evento auditado.
     *
     * @return BelongsTo<User, AuditLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Modelo afectado por el evento auditado.
     *
     * @return MorphTo<Model, AuditLog>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Filtra eventos por nombre.
     *
     * @param Builder<AuditLog> $query
     * @param string $event
     * @return Builder<AuditLog>
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Filtra eventos de un request especifico.
     *
     * @param Builder<AuditLog> $query
     * @param string $requestId
     * @return Builder<AuditLog>
     */
    public function scopeForRequest(Builder $query, string $requestId): Builder
    {
        return $query->where('request_id', $requestId);
    }
}
