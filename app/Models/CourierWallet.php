<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Billetera operativa del repartidor.
 */
class CourierWallet extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'available_balance',
        'pending_balance',
        'cash_balance',
        'negative_balance',
        'last_settlement_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'cash_balance' => 'decimal:2',
            'negative_balance' => 'decimal:2',
            'last_settlement_at' => 'datetime',
        ];
    }

    /**
     * Usuario repartidor.
     *
     * @return BelongsTo<User, CourierWallet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
