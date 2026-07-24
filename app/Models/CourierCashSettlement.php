<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCashSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'courier_wallet_id',
        'status',
        'expected_amount',
        'reported_amount',
        'approved_amount',
        'courier_notes',
        'admin_notes',
        'settlement_reference',
        'receipt_path',
        'received_by_user_id',
        'approved_by_user_id',
        'requested_at',
        'received_at',
        'approved_at',
        'rejected_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'reported_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'received_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CourierWallet::class, 'courier_wallet_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
