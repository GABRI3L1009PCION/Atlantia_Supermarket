<?php

namespace App\Models\Cliente;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Direccion de entrega registrada por un cliente.
 *
 * @property int $id
 * @property string $uuid
 * @property string $municipio
 */
class Direccion extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     *
     * @var string
     */
    protected $table = 'direcciones';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'alias',
        'nombre_contacto',
        'telefono_contacto',
        'municipio',
        'zona_o_barrio',
        'direccion_linea_1',
        'direccion_linea_2',
        'referencia',
        'latitude',
        'longitude',
        'mapbox_place_id',
        'es_principal',
        'activa',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telefono_contacto' => 'encrypted',
            'direccion_linea_1' => 'encrypted',
            'direccion_linea_2' => 'encrypted',
            'referencia' => 'encrypted',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usuario propietario de la direccion.
     *
     * @return BelongsTo<User, Direccion>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedidos entregados en esta direccion.
     *
     * @return HasMany<Pedido>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Filtra direcciones activas.
     *
     * @param Builder<Direccion> $query
     * @return Builder<Direccion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Filtra la direccion principal.
     *
     * @param Builder<Direccion> $query
     * @return Builder<Direccion>
     */
    public function scopePrincipal(Builder $query): Builder
    {
        return $query->where('es_principal', true);
    }

    /**
     * Filtra direcciones por municipio.
     *
     * @param Builder<Direccion> $query
     * @param string $municipio
     * @return Builder<Direccion>
     */
    public function scopeMunicipio(Builder $query, string $municipio): Builder
    {
        return $query->where('municipio', $municipio);
    }
}
