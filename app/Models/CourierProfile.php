<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perfil operativo del repartidor.
 */
class CourierProfile extends Model
{
    use HasFactory;

    /**
     * Estados de disponibilidad aceptados.
     *
     * @var array<int, string>
     */
    public const AVAILABILITY_STATUSES = [
        'offline',
        'available',
        'busy',
        'paused',
        'emergency',
    ];

    /**
     * Alcances de servicio aceptados.
     *
     * @var array<int, string>
     */
    public const SERVICE_SCOPES = [
        'internal',
        'entrepreneurs',
        'external',
        'both',
    ];

    /**
     * Niveles del programa de recompensas.
     *
     * @var array<int, string>
     */
    public const REWARD_LEVELS = [
        'Aprendiz',
        'Novato',
        'Amateur',
        'Master',
        'Experto',
        'Leyenda',
        'Leyenda Pro',
    ];

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'availability_status',
        'service_scope',
        'vehicle_type',
        'reward_level',
        'reward_points',
        'rating',
        'acceptance_rate',
        'completion_rate',
        'auto_accept_enabled',
        'auto_accept_max_distance_km',
        'safe_zones',
        'insurance_active',
        'insurance_notes',
        'bank_name',
        'bank_account_type',
        'bank_account_number',
        'bank_account_holder',
        'last_online_at',
        'last_offline_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reward_points' => 'integer',
            'rating' => 'decimal:2',
            'acceptance_rate' => 'decimal:2',
            'completion_rate' => 'decimal:2',
            'auto_accept_enabled' => 'boolean',
            'auto_accept_max_distance_km' => 'decimal:2',
            'safe_zones' => 'array',
            'insurance_active' => 'boolean',
            'last_online_at' => 'datetime',
            'last_offline_at' => 'datetime',
        ];
    }

    /**
     * Usuario repartidor.
     *
     * @return BelongsTo<User, CourierProfile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filtra repartidores disponibles.
     *
     * @param  Builder<CourierProfile>  $query
     * @return Builder<CourierProfile>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('availability_status', 'available');
    }
}
