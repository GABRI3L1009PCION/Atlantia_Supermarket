<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\Cliente\Direccion;
use App\Models\Dte\DteFactura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pedido de compra, padre o hijo por vendedor.
 *
 * @property int $id
 * @property string $uuid
 * @property string $numero_pedido
 * @property string $estado
 */
class Pedido extends Model
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
        'numero_pedido',
        'pedido_padre_id',
        'cliente_id',
        'vendor_id',
        'direccion_id',
        'dte_id',
        'subtotal',
        'envio',
        'impuestos',
        'descuento',
        'total',
        'estado',
        'metodo_pago',
        'estado_pago',
        'fraud_score',
        'fraud_revisado',
        'notas',
        'confirmado_at',
        'cancelado_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'envio' => 'decimal:2',
            'impuestos' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'estado' => EstadoPedido::class,
            'metodo_pago' => MetodoPago::class,
            'estado_pago' => EstadoPago::class,
            'fraud_score' => 'decimal:4',
            'fraud_revisado' => 'boolean',
            'confirmado_at' => 'datetime',
            'cancelado_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Usa UUID para rutas publicas del pedido.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Pedido padre cuando el pedido pertenece a un vendedor.
     *
     * @return BelongsTo<Pedido, Pedido>
     */
    public function pedidoPadre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pedido_padre_id');
    }

    /**
     * Pedidos hijos generados por vendedor.
     *
     * @return HasMany<Pedido>
     */
    public function pedidosHijos(): HasMany
    {
        return $this->hasMany(self::class, 'pedido_padre_id');
    }

    /**
     * Cliente que realizo el pedido.
     *
     * @return BelongsTo<User, Pedido>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * Vendedor asociado al pedido hijo.
     *
     * @return BelongsTo<Vendor, Pedido>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Direccion de entrega del pedido.
     *
     * @return BelongsTo<Direccion, Pedido>
     */
    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    /**
     * DTE principal asociado al pedido.
     *
     * @return BelongsTo<DteFactura, Pedido>
     */
    public function dte(): BelongsTo
    {
        return $this->belongsTo(DteFactura::class, 'dte_id');
    }

    /**
     * DTEs emitidos con referencia al pedido.
     *
     * @return HasMany<DteFactura>
     */
    public function dteFacturas(): HasMany
    {
        return $this->hasMany(DteFactura::class);
    }

    /**
     * Items del pedido.
     *
     * @return HasMany<PedidoItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * Historico de estados del pedido.
     *
     * @return HasMany<PedidoEstado>
     */
    public function estados(): HasMany
    {
        return $this->hasMany(PedidoEstado::class);
    }

    /**
     * Historial ampliado de transiciones del pedido.
     *
     * @return HasMany<PedidoHistorialEstado>
     */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(PedidoHistorialEstado::class)->latest();
    }

    /**
     * Pagos asociados al pedido.
     *
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Devoluciones solicitadas sobre el pedido.
     *
     * @return HasMany<Devolucion>
     */
    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class);
    }

    /**
     * Ruta de entrega asociada al pedido.
     *
     * @return HasOne<DeliveryRoute>
     */
    public function deliveryRoute(): HasOne
    {
        return $this->hasOne(DeliveryRoute::class);
    }

    /**
     * Resenas originadas por el pedido.
     *
     * @return HasMany<Resena>
     */
    public function resenas(): HasMany
    {
        return $this->hasMany(Resena::class);
    }

    /**
     * Filtra pedidos por estado.
     *
     * @param Builder<Pedido> $query
     * @param string $estado
     * @return Builder<Pedido>
     */
    public function scopeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Filtra pedidos pendientes.
     *
     * @param Builder<Pedido> $query
     * @return Builder<Pedido>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Filtra pedidos padres multivendedor.
     *
     * @param Builder<Pedido> $query
     * @return Builder<Pedido>
     */
    public function scopePadres(Builder $query): Builder
    {
        return $query->whereNull('pedido_padre_id');
    }

    /**
     * Filtra pedidos hijos por vendedor.
     *
     * @param Builder<Pedido> $query
     * @return Builder<Pedido>
     */
    public function scopeHijos(Builder $query): Builder
    {
        return $query->whereNotNull('pedido_padre_id');
    }

    /**
     * Devuelve estado como texto plano para vistas y payloads.
     */
    public function estadoValor(): string
    {
        return $this->estado instanceof EstadoPedido ? $this->estado->value : (string) $this->estado;
    }

    /**
     * Devuelve estado de pago como texto plano para vistas y payloads.
     */
    public function estadoPagoValor(): string
    {
        return $this->estado_pago instanceof EstadoPago ? $this->estado_pago->value : (string) $this->estado_pago;
    }

    /**
     * Devuelve metodo de pago como texto plano para vistas y payloads.
     */
    public function metodoPagoValor(): string
    {
        return $this->metodo_pago instanceof MetodoPago ? $this->metodo_pago->value : (string) $this->metodo_pago;
    }
}
