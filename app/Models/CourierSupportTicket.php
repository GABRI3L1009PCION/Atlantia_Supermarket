<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'assigned_to_user_id',
        'type',
        'priority',
        'status',
        'channel',
        'message',
        'support_response',
        'metadata',
        'last_message_at',
        'resolved_at',
        'closed_at',
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
            'last_message_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
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

    public function messages(): HasMany
    {
        return $this->hasMany(CourierSupportMessage::class)->orderBy('created_at');
    }
}
