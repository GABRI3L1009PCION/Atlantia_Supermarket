<?php

namespace App\Models\Dte;

use App\Models\Pedido;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Factura electronica DTE emitida por un vendedor.
 *
 * @property int $id
 * @property string $uuid
 * @property string $numero_dte
 * @property string $estado
 */
class DteFactura extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'dte_facturas';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'pedido_id',
        'vendor_id',
        'numero_dte',
        'uuid_sat',
        'serie',
        'numero',
        'tipo_dte',
        'monto_neto',
        'monto_iva',
        'monto_total',
        'moneda',
        'xml_dte',
        'pdf_path',
        'estado',
        'fecha_certificacion',
        'certificador_respuesta',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'monto_neto' => 'decimal:2',
            'monto_iva' => 'decimal:2',
            'monto_total' => 'decimal:2',
            'fecha_certificacion' => 'datetime',
            'certificador_respuesta' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Pedido relacionado con la factura.
     *
     * @return BelongsTo<Pedido, DteFactura>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Vendedor emisor de la factura.
     *
     * @return BelongsTo<Vendor, DteFactura>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Items fiscales incluidos en la factura.
     *
     * @return HasMany<DteItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DteItem::class, 'dte_id');
    }

    /**
     * Anulacion fiscal de la factura.
     *
     * @return HasOne<DteAnulacion>
     */
    public function anulacion(): HasOne
    {
        return $this->hasOne(DteAnulacion::class, 'dte_id');
    }

    /**
     * Comision mensual asociada cuando el DTE corresponde a Atlantia.
     *
     * @return HasOne<VendorCommission>
     */
    public function vendorCommission(): HasOne
    {
        return $this->hasOne(VendorCommission::class, 'dte_comision_id');
    }

    /**
     * Filtra facturas certificadas.
     *
     * @param Builder<DteFactura> $query
     * @return Builder<DteFactura>
     */
    public function scopeCertificadas(Builder $query): Builder
    {
        return $query->where('estado', 'certificado');
    }

    /**
     * Filtra facturas anuladas.
     *
     * @param Builder<DteFactura> $query
     * @return Builder<DteFactura>
     */
    public function scopeAnuladas(Builder $query): Builder
    {
        return $query->where('estado', 'anulado');
    }

    /**
     * Filtra facturas rechazadas.
     *
     * @param Builder<DteFactura> $query
     * @return Builder<DteFactura>
     */
    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('estado', 'rechazado');
    }

    /**
     * Filtra facturas por tipo DTE.
     *
     * @param Builder<DteFactura> $query
     * @param string $tipoDte
     * @return Builder<DteFactura>
     */
    public function scopeTipoDte(Builder $query, string $tipoDte): Builder
    {
        return $query->where('tipo_dte', $tipoDte);
    }
}
