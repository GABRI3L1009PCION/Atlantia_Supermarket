<?php

namespace App\Models;

use App\Models\Cliente\ClienteDetalle;
use App\Models\Cliente\Direccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario principal del sistema Atlantia.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string $status
 */
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens;
    use HasFactory;
    use MustVerifyEmail;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'status',
        'is_system_user',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_confirmed_at',
    ];

    /**
     * Atributos ocultos en serializacion.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_system_user' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Perfil de vendedor asociado al usuario.
     *
     * @return HasOne<Vendor>
     */
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Perfil de empleado interno asociado al usuario.
     *
     * @return HasOne<Empleado>
     */
    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class);
    }

    /**
     * Detalle del perfil de cliente.
     *
     * @return HasOne<ClienteDetalle>
     */
    public function clienteDetalle(): HasOne
    {
        return $this->hasOne(ClienteDetalle::class);
    }

    /**
     * Direcciones de entrega del cliente.
     *
     * @return HasMany<Direccion>
     */
    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }

    /**
     * Pedidos realizados por el cliente.
     *
     * @return HasMany<Pedido>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    /**
     * Registros de auditoria generados por el usuario.
     *
     * @return HasMany<AuditLog>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Emails enviados al usuario.
     *
     * @return HasMany<SentEmail>
     */
    public function sentEmails(): HasMany
    {
        return $this->hasMany(SentEmail::class);
    }

    /**
     * Filtra usuarios activos.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Filtra usuarios suspendidos.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Filtra usuarios internos del sistema.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeSystemUsers(Builder $query): Builder
    {
        return $query->where('is_system_user', true);
    }

    /**
     * Filtra usuarios visibles para administradores operativos.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeVisibleToOperationalAdmin(Builder $query): Builder
    {
        return $query->whereDoesntHave('roles', function (Builder $roleQuery): void {
            $roleQuery->where('name', 'super_admin');
        });
    }

    /**
     * Indica si el usuario tiene privilegios de super administrador.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin')
            || (
                config('atlantia.super_admin.enabled')
                && $this->email === config('atlantia.super_admin.email')
            );
    }
}
