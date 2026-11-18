<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidBalanceSnapshot extends Model
{
    protected $fillable = [
        'idempotency_key', 'cash_balance', 'bank_balance', 'mobile_wallet_balance',
        'other_liquid_balance', 'courier_reported_balance', 'note', 'verified_at', 'recorded_by',
    ];

    protected $casts = [
        'cash_balance' => 'decimal:2',
        'bank_balance' => 'decimal:2',
        'mobile_wallet_balance' => 'decimal:2',
        'other_liquid_balance' => 'decimal:2',
        'courier_reported_balance' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function getMoneyOnHandAttribute(): float
    {
        return round(
            (float) $this->cash_balance + (float) $this->bank_balance
            + (float) $this->mobile_wallet_balance + (float) $this->other_liquid_balance,
            2
        );
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
