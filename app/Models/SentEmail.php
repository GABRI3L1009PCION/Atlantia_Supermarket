<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoria de emails enviados por Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property string $to
 * @property string $status
 */
class SentEmail extends Model
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
        'to',
        'subject',
        'template',
        'status',
        'error',
        'metadata',
        'sent_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Usuario relacionado con el email enviado.
     *
     * @return BelongsTo<User, SentEmail>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filtra emails enviados correctamente.
     *
     * @param Builder<SentEmail> $query
     * @return Builder<SentEmail>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * Filtra emails fallidos.
     *
     * @param Builder<SentEmail> $query
     * @return Builder<SentEmail>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Filtra emails por plantilla.
     *
     * @param Builder<SentEmail> $query
     * @param string $template
     * @return Builder<SentEmail>
     */
    public function scopeTemplate(Builder $query, string $template): Builder
    {
        return $query->where('template', $template);
    }
}
