<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pago asociado a un pedido.
 *
 * @property int $id
 * @property string $uuid
 * @property string $metodo
 * @property string $estado
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * Asocia la factory local con el modelo.
     */
    protected static function newFactory(): \Database\Factories\PagoFactory
    {
        return \Database\Factories\PagoFactory::new();
    }

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'pedido_id',
        'metodo',
        'monto',
        'estado',
        'transaccion_id_pasarela',
        'hmac_validado',
        'referencia_bancaria',
        'validado_por',
        'validado_at',
        'pasarela_payload',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'metodo' => MetodoPago::class,
            'estado' => EstadoPago::class,
            'hmac_validado' => 'boolean',
            'validado_at' => 'datetime',
            'pasarela_payload' => 'array',
        ];
    }

    /**
     * Pedido al que pertenece el pago.
     *
     * @return BelongsTo<Pedido, Payment>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Usuario que valido el pago.
     *
     * @return BelongsTo<User, Payment>
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    /**
     * Splits del pago hacia vendedores.
     *
     * @return HasMany<PaymentSplit>
     */
    public function splits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class);
    }

    /**
     * Filtra pagos aprobados.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('estado', 'aprobado');
    }

    /**
     * Filtra pagos pendientes.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Filtra pagos por metodo.
     *
     * @param Builder<Payment> $query
     * @param string $metodo
     * @return Builder<Payment>
     */
    public function scopeMetodo(Builder $query, string $metodo): Builder
    {
        return $query->where('metodo', $metodo);
    }

    /**
     * Devuelve metodo como texto plano para vistas y payloads.
     */
    public function metodoValor(): string
    {
        return $this->metodo instanceof MetodoPago ? $this->metodo->value : (string) $this->metodo;
    }

    /**
     * Devuelve estado como texto plano para vistas y payloads.
     */
    public function estadoValor(): string
    {
        return $this->estado instanceof EstadoPago ? $this->estado->value : (string) $this->estado;
    }
}
