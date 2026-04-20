<?php

namespace App\Models;

use App\Models\Dte\DteFactura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comision y renta mensual cobrada por Atlantia a un vendedor.
 *
 * @property int $id
 * @property string $uuid
 * @property int $anio
 * @property int $mes
 * @property string $estado
 */
class VendorCommission extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'vendor_id',
        'anio',
        'mes',
        'total_ventas',
        'commission_percentage',
        'monto_comision',
        'renta_fija',
        'monto_total',
        'estado',
        'dte_comision_id',
        'fecha_emision',
        'fecha_vencimiento',
        'pagada_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'mes' => 'integer',
            'total_ventas' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'monto_comision' => 'decimal:2',
            'renta_fija' => 'decimal:2',
            'monto_total' => 'decimal:2',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'pagada_at' => 'datetime',
        ];
    }

    /**
     * Vendedor al que pertenece la comision.
     *
     * @return BelongsTo<Vendor, VendorCommission>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * DTE emitido por la comision mensual.
     *
     * @return BelongsTo<DteFactura, VendorCommission>
     */
    public function dteComision(): BelongsTo
    {
        return $this->belongsTo(DteFactura::class, 'dte_comision_id');
    }

    /**
     * Filtra comisiones pendientes.
     *
     * @param Builder<VendorCommission> $query
     * @return Builder<VendorCommission>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Filtra comisiones facturadas.
     *
     * @param Builder<VendorCommission> $query
     * @return Builder<VendorCommission>
     */
    public function scopeFacturada(Builder $query): Builder
    {
        return $query->where('estado', 'facturada');
    }

    /**
     * Filtra comisiones pagadas.
     *
     * @param Builder<VendorCommission> $query
     * @return Builder<VendorCommission>
     */
    public function scopePagada(Builder $query): Builder
    {
        return $query->where('estado', 'pagada');
    }

    /**
     * Filtra comisiones por periodo.
     *
     * @param Builder<VendorCommission> $query
     * @param int $anio
     * @param int $mes
     * @return Builder<VendorCommission>
     */
    public function scopePeriodo(Builder $query, int $anio, int $mes): Builder
    {
        return $query->where('anio', $anio)->where('mes', $mes);
    }
}
