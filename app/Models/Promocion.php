<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Promocion automatica: "lleva N paga M" (2x1, 3x2) o porcentaje (oferta relampago por horario).
 */
class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'uuid',
        'vendor_id',
        'nombre',
        'tipo',
        'lleva',
        'paga',
        'porcentaje',
        'producto_id',
        'categoria_id',
        'inicia_at',
        'termina_at',
        'hora_inicio',
        'hora_fin',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'lleva' => 'integer',
            'paga' => 'integer',
            'porcentaje' => 'decimal:2',
            'inicia_at' => 'datetime',
            'termina_at' => 'datetime',
            'activa' => 'boolean',
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

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Promociones activas en este momento (fecha y, si aplica, franja horaria del dia).
     */
    public function scopeVigentes(Builder $query, ?Carbon $ahora = null): Builder
    {
        $ahora ??= now();
        $hora = $ahora->format('H:i:s');

        return $query->where('activa', true)
            ->where(fn (Builder $q) => $q->whereNull('inicia_at')->orWhere('inicia_at', '<=', $ahora))
            ->where(fn (Builder $q) => $q->whereNull('termina_at')->orWhere('termina_at', '>=', $ahora))
            ->where(fn (Builder $q) => $q->whereNull('hora_inicio')
                ->orWhere(fn (Builder $franja) => $franja->where('hora_inicio', '<=', $hora)->where('hora_fin', '>', $hora)));
    }

    /**
     * Etiqueta corta para mostrar en tienda: "2x1", "3x2", "-20%".
     */
    public function etiqueta(): string
    {
        return $this->tipo === 'nxm'
            ? "{$this->lleva}x{$this->paga}"
            : '-'.rtrim(rtrim(number_format((float) $this->porcentaje, 2), '0'), '.').'%';
    }

    public function esRelampago(): bool
    {
        return $this->hora_inicio !== null;
    }
}
