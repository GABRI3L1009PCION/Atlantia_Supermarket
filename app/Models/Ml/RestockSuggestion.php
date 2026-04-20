<?php

namespace App\Models\Ml;

use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sugerencia de reabastecimiento generada por ML.
 *
 * @property int $id
 * @property string $urgencia
 */
class RestockSuggestion extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'producto_id',
        'vendor_id',
        'stock_actual',
        'stock_sugerido',
        'dias_hasta_quiebre',
        'urgencia',
        'aceptada',
        'modelo_version_id',
        'aceptada_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_actual' => 'integer',
            'stock_sugerido' => 'integer',
            'dias_hasta_quiebre' => 'integer',
            'aceptada' => 'boolean',
            'aceptada_at' => 'datetime',
        ];
    }

    /**
     * Producto asociado a la sugerencia.
     *
     * @return BelongsTo<Producto, RestockSuggestion>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Vendedor destinatario de la sugerencia.
     *
     * @return BelongsTo<Vendor, RestockSuggestion>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Version de modelo que genero la sugerencia.
     *
     * @return BelongsTo<MlModelVersion, RestockSuggestion>
     */
    public function modeloVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'modelo_version_id');
    }

    /**
     * Filtra sugerencias pendientes.
     *
     * @param Builder<RestockSuggestion> $query
     * @return Builder<RestockSuggestion>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('aceptada', false);
    }

    /**
     * Filtra sugerencias urgentes.
     *
     * @param Builder<RestockSuggestion> $query
     * @return Builder<RestockSuggestion>
     */
    public function scopeUrgentes(Builder $query): Builder
    {
        return $query->whereIn('urgencia', ['alta', 'critica']);
    }
}
