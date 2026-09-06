<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'amount',
        'expense_date',
        'category',
        'note',
        'fund_transaction_id',
        'created_by',
        'updated_by',
        'excluded_from_accounting_at',
        'accounting_exclusion_reason',
        'accounting_cleanup_run_id',
    ];

    protected $casts = [
        'excluded_from_accounting_at' => 'datetime',
    ];

    public function scopeIncludedInAccounting(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('excluded_from_accounting_at'));
    }

    public function fundTransaction()
    {
        return $this->belongsTo(FundTransaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
