<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Division de pago por vendedor.
 *
 * @property int $id
 * @property string $estado
 */
class PaymentSplit extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'vendor_id',
        'monto_bruto',
        'comision_atlantia',
        'monto_neto_vendedor',
        'estado',
        'liquidado_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto_bruto' => 'decimal:2',
            'comision_atlantia' => 'decimal:2',
            'monto_neto_vendedor' => 'decimal:2',
            'liquidado_at' => 'datetime',
        ];
    }

    /**
     * Pago origen del split.
     *
     * @return BelongsTo<Payment, PaymentSplit>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Vendedor receptor del monto neto.
     *
     * @return BelongsTo<Vendor, PaymentSplit>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Filtra splits pendientes.
     *
     * @param Builder<PaymentSplit> $query
     * @return Builder<PaymentSplit>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Filtra splits liquidados.
     *
     * @param Builder<PaymentSplit> $query
     * @return Builder<PaymentSplit>
     */
    public function scopeLiquidado(Builder $query): Builder
    {
        return $query->where('estado', 'liquidado');
    }
}
