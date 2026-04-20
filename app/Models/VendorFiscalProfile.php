<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perfil fiscal FEL de un vendedor.
 *
 * @property int $id
 * @property string $nit
 * @property string $razon_social
 */
class VendorFiscalProfile extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_id',
        'nit',
        'razon_social',
        'nombre_comercial_sat',
        'direccion_fiscal',
        'regimen_sat',
        'codigo_establecimiento',
        'afiliacion_iva',
        'certificador_fel',
        'fel_usuario',
        'fel_llave_firma',
        'fel_llave_certificador',
        'banco_nombre',
        'cuenta_bancaria',
        'cuenta_bancaria_tipo',
        'cuenta_bancaria_titular',
        'fel_activo',
        'fel_validado_at',
        'fel_validado_por',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direccion_fiscal' => 'encrypted',
            'fel_usuario' => 'encrypted',
            'fel_llave_firma' => 'encrypted',
            'fel_llave_certificador' => 'encrypted',
            'cuenta_bancaria' => 'encrypted',
            'fel_activo' => 'boolean',
            'fel_validado_at' => 'datetime',
        ];
    }

    /**
     * Vendedor propietario del perfil fiscal.
     *
     * @return BelongsTo<Vendor, VendorFiscalProfile>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Usuario que valido la configuracion FEL.
     *
     * @return BelongsTo<User, VendorFiscalProfile>
     */
    public function felValidadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fel_validado_por');
    }

    /**
     * Filtra perfiles con FEL activo.
     *
     * @param Builder<VendorFiscalProfile> $query
     * @return Builder<VendorFiscalProfile>
     */
    public function scopeFelActivo(Builder $query): Builder
    {
        return $query->where('fel_activo', true);
    }

    /**
     * Filtra perfiles por regimen SAT.
     *
     * @param Builder<VendorFiscalProfile> $query
     * @param string $regimen
     * @return Builder<VendorFiscalProfile>
     */
    public function scopeRegimenSat(Builder $query, string $regimen): Builder
    {
        return $query->where('regimen_sat', $regimen);
    }
}
