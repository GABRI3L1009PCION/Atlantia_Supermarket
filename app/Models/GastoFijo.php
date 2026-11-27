<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gasto fijo mensual de Atlantia (servidor, dominio, internet, sueldos, etc.).
 */
class GastoFijo extends Model
{
    protected $table = 'gastos_fijos';

    public const CATEGORIAS = [
        'tecnologia' => 'Servidor, dominio y apps',
        'operacion' => 'Operación (local, luz, internet)',
        'personal' => 'Sueldos y personal',
        'marketing' => 'Publicidad y marketing',
        'impuestos' => 'Impuestos y contador',
        'otros' => 'Otros',
    ];

    protected $fillable = ['mes', 'categoria', 'concepto', 'monto', 'notas', 'registrado_por'];

    protected function casts(): array
    {
        return [
            'mes' => 'date',
            'monto' => 'decimal:2',
        ];
    }
}
