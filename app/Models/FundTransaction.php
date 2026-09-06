<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundTransaction extends Model
{
    private const MANUALLY_EDITABLE_SOURCES = ['manual_add', 'withdraw'];

    protected $fillable = [
        'direction', 'source', 'source_id', 'amount', 'note', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        // Status updates arrive through overlapping admin and courier paths.
        static::creating(function (self $transaction) {
            if ($transaction->direction !== 'in' || $transaction->source !== 'sale' || !$transaction->source_id) {
                return null;
            }

            $order = Order::query()->find($transaction->source_id);
            if (
                !$order ||
                (int) $order->order_status !== \App\Services\InventoryService::COMPLETE_STATUS ||
                strtolower((string) $order->payment_status) !== 'paid'
            ) {
                return false;
            }

            return !self::query()
                ->where('direction', 'in')
                ->where('source', 'sale')
                ->where('source_id', $transaction->source_id)
                ->exists();
        });
    }

    public function isManuallyEditable(): bool
    {
        return in_array($this->source, self::MANUALLY_EDITABLE_SOURCES, true);
    }

    /**
     * Get all logs for this transaction
     */
    public function logs(): HasMany
    {
        return $this->hasMany(FundTransactionLog::class, 'fund_transaction_id');
    }

    /**
     * Check if this transaction has been edited
     */
    public function hasBeenEdited(): bool
    {
        return $this->logs()->where('action', 'edit')->exists();
    }

    /**
     * Check if this transaction has been deleted (if it still exists, it means delete was cancelled or restored)
     */
    public function hasDeleteLog(): bool
    {
        return $this->logs()->where('action', 'delete')->exists();
    }

    /**
     * Get the latest edit log
     */
    public function latestEditLog()
    {
        return $this->logs()->where('action', 'edit')->latest()->first();
    }
}
