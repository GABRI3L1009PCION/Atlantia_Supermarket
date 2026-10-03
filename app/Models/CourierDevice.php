<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispositivo movil registrado por un repartidor para notificaciones y telemetria.
 */
class CourierDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'platform',
        'device_uuid',
        'device_name',
        'app_version',
        'push_provider',
        'push_token',
        'notifications_enabled',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'push_token' => 'encrypted',
            'notifications_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Repartidor propietario del dispositivo.
     *
     * @return BelongsTo<User, CourierDevice>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
