<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Denuncia de un cliente contra una tienda (no entregó, fraude, productos ilegales, mal uso...).
 */
class DenunciaTienda extends Model
{
    protected $table = 'denuncias_tienda';

    public const MOTIVOS = [
        'no_entrego' => 'No entregó mi pedido',
        'producto_diferente' => 'Me entregó algo diferente o en mal estado',
        'cobro_indebido' => 'Me cobró de más o me pidió pagar por fuera',
        'fraude' => 'Fraude o estafa',
        'producto_ilegal' => 'Vende productos ilegales o prohibidos',
        'trato' => 'Trato ofensivo o acoso',
        'mal_uso' => 'Mal uso de la plataforma',
        'otro' => 'Otro motivo',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = ['uuid', 'vendor_id', 'cliente_id', 'pedido_id', 'motivo', 'descripcion', 'estado', 'resolucion', 'revisada_por', 'revisada_at'];

    protected function casts(): array
    {
        return ['revisada_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Vendor, DenunciaTienda>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<User, DenunciaTienda>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * @return BelongsTo<Pedido, DenunciaTienda>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return BelongsTo<User, DenunciaTienda>
     */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }
}
