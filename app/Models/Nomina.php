<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Planilla de pago para empleados internos.
 */
class Nomina extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'periodo_inicio',
        'periodo_fin',
        'tipo_periodo',
        'estado',
        'total_bruto',
        'total_bonificaciones',
        'total_descuentos',
        'total_neto',
        'generada_por',
        'pagada_por',
        'pagada_at',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'total_bruto' => 'decimal:2',
            'total_bonificaciones' => 'decimal:2',
            'total_descuentos' => 'decimal:2',
            'total_neto' => 'decimal:2',
            'pagada_at' => 'datetime',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(NominaDetalle::class);
    }

    public function generadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    public function pagadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagada_por');
    }
}
