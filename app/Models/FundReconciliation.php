<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundReconciliation extends Model
{
    protected $fillable = [
        'system_balance_before',
        'cash_balance',
        'bank_balance',
        'mobile_wallet_balance',
        'other_balance',
        'actual_balance',
        'difference',
        'fund_transaction_id',
        'note',
        'reconciled_by',
    ];

    protected $casts = [
        'system_balance_before' => 'decimal:2',
        'cash_balance' => 'decimal:2',
        'bank_balance' => 'decimal:2',
        'mobile_wallet_balance' => 'decimal:2',
        'other_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function fundTransaction(): BelongsTo
    {
        return $this->belongsTo(FundTransaction::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
