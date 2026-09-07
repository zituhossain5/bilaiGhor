<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FundTransaction;

final class ExpenseFundTransactionService
{
    public function sync(Expense $expense, ?int $userId): FundTransaction
    {
        $transactions = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'out')
            ->where('source', 'expense')
            ->where('source_id', $expense->id)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        $fund = $transactions->first(
            fn (FundTransaction $transaction) => (int) $transaction->id === (int) $expense->fund_transaction_id
        ) ?? $transactions->first();

        $attributes = [
            'direction' => 'out',
            'source' => 'expense',
            'source_id' => $expense->id,
            'amount' => $expense->amount,
            'note' => $this->noteFor($expense),
        ];

        if ($fund) {
            $fund->update($attributes + ['updated_by' => $userId]);
        } else {
            $fund = FundTransaction::create($attributes + ['created_by' => $userId]);
        }

        $transactions
            ->where('id', '!=', $fund->id)
            ->each(function (FundTransaction $duplicate) use ($fund, $userId) {
                $duplicate->update([
                    'excluded_from_accounting_at' => now(),
                    'accounting_exclusion_reason' => 'Duplicate expense transaction; active transaction is #' . $fund->id,
                    'updated_by' => $userId,
                ]);
            });

        if ((int) $expense->fund_transaction_id !== (int) $fund->id) {
            $expense->update(['fund_transaction_id' => $fund->id]);
        }

        return $fund;
    }

    public function deleteFor(Expense $expense): float
    {
        $transactions = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'out')
            ->where('source', 'expense')
            ->where('source_id', $expense->id)
            ->lockForUpdate()
            ->get();

        $amount = (float) $transactions->sum('amount');

        $transactions->each->delete();

        return $amount;
    }

    private function noteFor(Expense $expense): string
    {
        return 'Expense: ' . $expense->title . ($expense->note ? ' - ' . $expense->note : '');
    }
}
