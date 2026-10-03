<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierWithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'courier_wallet_id',
        'status',
        'payout_method',
        'requested_amount',
        'approved_amount',
        'transferred_amount',
        'bank_name',
        'bank_account_type',
        'bank_account_number_last4',
        'bank_account_holder',
        'courier_notes',
        'admin_notes',
        'transfer_reference',
        'receipt_path',
        'reviewed_by_user_id',
        'transferred_by_user_id',
        'requested_at',
        'reviewed_at',
        'approved_at',
        'transferred_at',
        'rejected_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'transferred_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'transferred_at' => 'datetime',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by_user_id');
    }
}
