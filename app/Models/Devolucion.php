<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Solicitud de devolucion o reembolso de un pedido.
 */
class Devolucion extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'devoluciones';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'pedido_id',
        'user_id',
        'motivo',
        'estado',
        'monto_reembolso',
        'descripcion',
        'notas_admin',
        'foto_evidencia',
        'resuelta_por',
        'resuelta_at',
    ];

    /**
     * Obtiene casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto_reembolso' => 'decimal:2',
            'resuelta_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap del modelo.
     */
    protected static function booted(): void
    {
        static::creating(function (Devolucion $devolucion): void {
            if (empty($devolucion->uuid)) {
                $devolucion->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Pedido relacionado.
     *
     * @return BelongsTo<Pedido, Devolucion>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Cliente solicitante.
     *
     * @return BelongsTo<User, Devolucion>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario administrativo que resolvio.
     *
     * @return BelongsTo<User, Devolucion>
     */
    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    /**
     * Usa UUID en rutas publicas.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Filtra devoluciones pendientes.
     *
     * @param Builder<Devolucion> $query
     * @return Builder<Devolucion>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'solicitada');
    }
}
