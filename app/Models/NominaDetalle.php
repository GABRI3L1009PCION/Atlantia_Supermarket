<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago individual incluido en una planilla.
 */
class NominaDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomina_id',
        'empleado_id',
        'salario_base',
        'bonificaciones',
        'descuentos',
        'total_neto',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'salario_base' => 'decimal:2',
            'bonificaciones' => 'decimal:2',
            'descuentos' => 'decimal:2',
            'total_neto' => 'decimal:2',
        ];
    }

    public function nomina(): BelongsTo
    {
        return $this->belongsTo(Nomina::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
