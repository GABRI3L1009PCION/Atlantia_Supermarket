<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Servicio que un comercio o anunciante paga a Atlantia: plan, banner, oferta destacada
 * en el aviso semanal o producto patrocinado en la busqueda.
 */
class ServicioContratado extends Model
{
    protected $table = 'servicios_contratados';

    protected $fillable = [
        'uuid',
        'vendor_id',
        'user_id',
        'tipo',
        'referencia_type',
        'referencia_id',
        'anunciante',
        'detalle',
        'monto',
        'inicia_at',
        'termina_at',
        'estado',
        'metodo_pago',
        'recurrente_checkout_id',
        'pagado_at',
        'vistas',
        'clics',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'monto' => 'decimal:2',
            'inicia_at' => 'datetime',
            'termina_at' => 'datetime',
            'pagado_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Servicios pagados y vigentes en este momento.
     */
    public function scopeVigentes(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo)
            ->where('estado', 'activo')
            ->where(fn (Builder $q) => $q->whereNull('inicia_at')->orWhere('inicia_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('termina_at')->orWhere('termina_at', '>=', now()));
    }

    public function etiquetaTipo(): string
    {
        return match ($this->tipo) {
            'plan' => 'Plan de vendedor',
            'banner' => 'Banner publicitario',
            'destacado' => 'Oferta destacada en el aviso semanal',
            'patrocinado' => 'Producto patrocinado en búsquedas',
            default => $this->tipo,
        };
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'pendiente_pago' => 'Pendiente de pago',
            'en_revision' => 'Pagado, en revisión',
            'activo' => 'Activo',
            'vencido' => 'Terminado',
            'cancelado' => 'Cancelado',
            'rechazado' => 'Rechazado',
            default => $this->estado,
        };
    }
}
