<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket de soporte del repartidor.
 */
class CourierSupportTicket extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'pedido_id',
        'external_delivery_order_id',
        'type',
        'priority',
        'status',
        'message',
        'support_response',
        'metadata',
        'resolved_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Usa UUID para rutas publicas.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Repartidor que abre el ticket.
     *
     * @return BelongsTo<User, CourierSupportTicket>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedido interno asociado.
     *
     * @return BelongsTo<Pedido, CourierSupportTicket>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Solicitud externa asociada.
     *
     * @return BelongsTo<ExternalDeliveryOrder, CourierSupportTicket>
     */
    public function externalOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalDeliveryOrder::class, 'external_delivery_order_id');
    }
}
