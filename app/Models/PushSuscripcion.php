<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispositivo (navegador o app instalada) que acepto recibir notificaciones push.
 */
class PushSuscripcion extends Model
{
    protected $table = 'push_suscripciones';

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'p256dh',
        'auth',
        'ofertas',
        'user_agent',
        'ultimo_envio_at',
    ];

    protected $hidden = ['endpoint', 'p256dh', 'auth'];

    protected function casts(): array
    {
        return [
            'ofertas' => 'boolean',
            'ultimo_envio_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
