<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Solicitud de pago de un vendedor: agrupa sus ventas entregadas y lo que Atlantia le transfiere.
 *
 * @property int $id
 * @property string $uuid
 * @property string $numero
 * @property string $estado solicitada|pagada|rechazada
 */
class LiquidacionVendedor extends Model
{
    protected $table = 'liquidaciones_vendedor';

    protected $fillable = [
        'uuid',
        'numero',
        'vendor_id',
        'ventas',
        'monto_bruto',
        'comision_plataforma',
        'comision_pasarela',
        'monto_neto',
        'estado',
        'banco_nombre',
        'cuenta_titular',
        'referencia_pago',
        'notas',
        'solicitada_at',
        'automatica',
        'pagar_antes_de',
        'pagada_at',
        'pagada_por',
    ];

    protected function casts(): array
    {
        return [
            'ventas' => 'integer',
            'monto_bruto' => 'decimal:2',
            'comision_plataforma' => 'decimal:2',
            'comision_pasarela' => 'decimal:2',
            'monto_neto' => 'decimal:2',
            'solicitada_at' => 'datetime',
            'automatica' => 'boolean',
            'pagar_antes_de' => 'datetime',
            'pagada_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Vendor, LiquidacionVendedor>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<PaymentSplit>
     */
    public function splits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class, 'liquidacion_id');
    }

    /**
     * @return BelongsTo<User, LiquidacionVendedor>
     */
    public function pagadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagada_por');
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'solicitada' => 'En proceso de pago',
            'pagada' => 'Pagada',
            'rechazada' => 'Rechazada',
            default => ucfirst((string) $this->estado),
        };
    }
}
