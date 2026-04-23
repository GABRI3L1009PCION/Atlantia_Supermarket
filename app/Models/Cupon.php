<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Cupon de descuento configurable desde administracion.
 */
class Cupon extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'codigo',
        'tipo',
        'valor',
        'minimo_compra',
        'maximo_descuento',
        'usos_maximos',
        'usos_actuales',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'solo_primera_compra',
        'descripcion',
    ];

    /**
     * Casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'minimo_compra' => 'decimal:2',
            'maximo_descuento' => 'decimal:2',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'activo' => 'boolean',
            'solo_primera_compra' => 'boolean',
        ];
    }

    /**
     * Inicializa UUID y codigo normalizado.
     */
    protected static function booted(): void
    {
        static::creating(function (Cupon $cupon): void {
            if (empty($cupon->uuid)) {
                $cupon->uuid = (string) Str::uuid();
            }

            $cupon->codigo = Str::upper(trim($cupon->codigo));
        });

        static::updating(function (Cupon $cupon): void {
            $cupon->codigo = Str::upper(trim($cupon->codigo));
        });
    }

    /**
     * Usos del cupon.
     *
     * @return HasMany<CuponUso>
     */
    public function usos(): HasMany
    {
        return $this->hasMany(CuponUso::class);
    }

    /**
     * Filtra cupones vigentes.
     *
     * @param Builder<Cupon> $query
     * @return Builder<Cupon>
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('activo', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            });
    }
}
