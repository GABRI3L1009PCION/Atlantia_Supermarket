<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Perfil de empleado interno de Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property string $codigo_empleado
 * @property string $departamento
 */
class Empleado extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'codigo_empleado',
        'departamento',
        'puesto',
        'telefono_interno',
        'fecha_contratacion',
        'status',
        'supervisor_id',
        'permisos_operativos',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_contratacion' => 'date',
            'permisos_operativos' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usuario asociado al empleado.
     *
     * @return BelongsTo<User, Empleado>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Supervisor directo del empleado.
     *
     * @return BelongsTo<Empleado, Empleado>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /**
     * Empleados supervisados por este empleado.
     *
     * @return HasMany<Empleado>
     */
    public function supervisados(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    /**
     * Mensajes de contacto atendidos por el empleado.
     *
     * @return HasMany<ContactMessage>
     */
    public function mensajesAtendidos(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'atendido_por', 'user_id');
    }

    /**
     * Filtra empleados activos.
     *
     * @param Builder<Empleado> $query
     * @return Builder<Empleado>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Filtra empleados por departamento.
     *
     * @param Builder<Empleado> $query
     * @param string $departamento
     * @return Builder<Empleado>
     */
    public function scopeDepartamento(Builder $query, string $departamento): Builder
    {
        return $query->where('departamento', $departamento);
    }
}
