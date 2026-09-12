<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundTransaction extends Model
{
    private const MANUALLY_EDITABLE_SOURCES = ['investment', 'manual_add', 'withdraw'];

    public const LEGACY_BUSINESS_SOURCES = [
        'vendor_commission',
        'vendor_withdrawal',
        'reseller_commission',
        'reseller_withdrawal',
    ];

    protected $fillable = [
        'direction', 'source', 'source_id', 'investment_type', 'amount', 'transaction_date', 'idempotency_key',
        'note', 'idempotency_key', 'created_by', 'updated_by',
        'excluded_from_accounting_at', 'accounting_exclusion_reason', 'accounting_cleanup_run_id',
    ];

    protected $casts = [
        'excluded_from_accounting_at' => 'datetime',
        'transaction_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Status updates arrive through overlapping admin and courier paths.
        static::creating(function (self $transaction) {
            if ($transaction->direction !== 'in' || $transaction->source !== 'sale' || ! $transaction->source_id) {
                return null;
            }

            $order = Order::query()->find($transaction->source_id);
            if (
                ! $order ||
                (int) $order->order_status !== \App\Services\InventoryService::COMPLETE_STATUS ||
                strtolower((string) $order->payment_status) !== 'paid'
            ) {
                return false;
            }

            return ! self::query()
                ->includedInAccounting()
                ->where('direction', 'in')
                ->where('source', 'sale')
                ->where('source_id', $transaction->source_id)
                ->exists();
        });
    }

    public function scopeIncludedInAccounting(Builder $query): Builder
    {
        return $query
            ->whereNull($query->qualifyColumn('excluded_from_accounting_at'))
            ->whereNotIn($query->qualifyColumn('source'), self::LEGACY_BUSINESS_SOURCES);
    }

    public function scopeExcludedFromAccounting(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('excluded_from_accounting_at'));
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
