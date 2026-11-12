<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseLog;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HistoricalPurchaseService
{
    public function reverse(Purchase $purchase, int $adminId): void
    {
        DB::transaction(function () use ($purchase, $adminId) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->purchase_mode !== 'historical' || $purchase->accounting_treatment === 'new_payment') {
                throw ValidationException::withMessages(['purchase' => 'This purchase requires manual accounting review before reversal.']);
            }
            $payments = $purchase->payments()->lockForUpdate()->get();
            if ($payments->count() > 1 || ($payments->first() && abs((float) $payments->first()->amount - (float) $purchase->paid_amount) > 0.009)) {
                throw ValidationException::withMessages(['purchase' => 'Additional supplier payments exist; reverse them through accounting review first.']);
            }
            if ($purchase->accounting_treatment === 'already_recorded' && $payments->contains(fn ($payment) => $payment->fund_transaction_id !== null)) {
                throw ValidationException::withMessages(['purchase' => 'A fund-linked payment exists; manual accounting review is required.']);
            }
            if ($purchase->reclassified_expense_id) {
                $expense = Expense::query()->lockForUpdate()->findOrFail($purchase->reclassified_expense_id);
                $payment = $payments->first();
                $fund = $payment?->fundTransaction()->lockForUpdate()->first();
                if (! $payment || ! $fund || $fund->source !== 'supplier_payment' ||
                    (int) $fund->source_id !== (int) $payment->id ||
                    (int) $expense->reclassified_purchase_id !== (int) $purchase->id) {
                    throw ValidationException::withMessages(['purchase' => 'Reclassification links changed; manual accounting review is required.']);
                }
                $fund->update(['source' => 'expense', 'source_id' => $expense->id]);
                $expense->update([
                    'reclassified_at' => null, 'reclassified_purchase_id' => null,
                    'excluded_from_accounting_at' => null, 'accounting_exclusion_reason' => null,
                ]);
            }
            $balance = AccountingSummaryService::fundBalance();
            PurchaseLog::create([
                'purchase_id' => $purchase->id, 'action' => 'historical_reverse',
                'old_invoice_no' => $purchase->invoice_no, 'old_purchase_date' => $purchase->purchase_date,
                'old_paid_amount' => $purchase->paid_amount, 'old_grand_total' => $purchase->grand_total,
                'fund_balance_before' => $balance, 'fund_balance_after' => $balance,
                'description' => 'Historical purchase reversed by admin; no stock movement. '.
                    ($purchase->reclassified_expense_id ? 'Expense #'.$purchase->reclassified_expense_id.' restored.' : 'Prior external payment retained outside ledger.'),
                'performed_by' => $adminId,
            ]);
            $supplier = Supplier::query()->lockForUpdate()->findOrFail($purchase->supplier_id);
            $supplier->current_due = round((float) $supplier->current_due - (float) $purchase->due_amount, 2);
            $supplier->save();
            $purchase->payments()->delete();
            $purchase->items()->delete();
            $purchase->delete();
        });
    }

    public function update(Purchase $purchase, array $data, int $adminId): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $adminId) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->purchase_mode !== 'historical') {
                throw ValidationException::withMessages(['purchase_mode' => 'Purchase mode cannot be changed.']);
            }
            $invoiceKey = $this->invoiceKey($purchase->supplier_id, $data['purchase_date'], $data['invoice_no']);
            if (Purchase::where('historical_invoice_key', $invoiceKey)->whereKeyNot($purchase->id)->exists()) {
                throw ValidationException::withMessages(['invoice_no' => 'This historical supplier invoice is already recorded.']);
            }
            $items = $purchase->items()->lockForUpdate()->get()->keyBy('id');
            $submitted = collect($data['lines'])->keyBy('item_id');
            if ($items->count() !== $submitted->count() || $items->keys()->diff($submitted->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['lines' => 'Historical product lines cannot be added or removed on edit.']);
            }
            $subtotal = 0;
            $qty = 0;
            foreach ($items as $item) {
                $line = $submitted[$item->id];
                $lineTotal = round((int) $line['qty'] * (float) $line['unit_cost'], 2);
                $item->update(['qty' => $line['qty'], 'unit_cost' => $line['unit_cost'], 'line_total' => $lineTotal]);
                $subtotal += $lineTotal;
                $qty += (int) $line['qty'];
            }
            $total = round($subtotal - (float) $purchase->discount + (float) $purchase->shipping_cost, 2);
            if ($total <= 0 || $total < (float) $purchase->paid_amount) {
                throw ValidationException::withMessages(['lines' => 'Total must stay positive and cannot be below recorded payments.']);
            }
            if ($purchase->reclassified_expense_id) {
                $expense = Expense::query()->lockForUpdate()->findOrFail($purchase->reclassified_expense_id);
                if (abs($total - (float) $expense->amount) > 0.009) {
                    throw ValidationException::withMessages(['lines' => 'Reclassified purchase must continue to match the original expense amount.']);
                }
            }
            $oldTotal = (float) $purchase->grand_total;
            $purchase->update([
                'invoice_no' => $data['invoice_no'], 'purchase_date' => $data['purchase_date'],
                'historical_invoice_key' => $invoiceKey,
                'total_qty' => $qty, 'subtotal' => $subtotal, 'grand_total' => $total,
                'due_amount' => round($total - (float) $purchase->paid_amount, 2),
                'note' => $data['note'] ?? null,
            ]);
            if ($oldTotal !== $total) {
                $supplier = Supplier::query()->lockForUpdate()->findOrFail($purchase->supplier_id);
                $supplier->current_due = round((float) $supplier->current_due + $total - $oldTotal, 2);
                $supplier->save();
            }
            $balance = AccountingSummaryService::fundBalance();
            PurchaseLog::create([
                'purchase_id' => $purchase->id, 'action' => 'edit',
                'old_grand_total' => $oldTotal, 'new_grand_total' => $total,
                'old_paid_amount' => $purchase->paid_amount, 'new_paid_amount' => $purchase->paid_amount,
                'new_invoice_no' => $purchase->invoice_no, 'new_purchase_date' => $purchase->purchase_date,
                'fund_balance_before' => $balance, 'fund_balance_after' => $balance,
                'description' => 'Historical purchase details edited; no current stock or fund movement.',
                'performed_by' => $adminId,
            ]);
            return $purchase;
        });
    }

    public function create(array $data, int $adminId): Purchase
    {
        return DB::transaction(function () use ($data, $adminId) {
            $invoiceKey = $this->invoiceKey($data['supplier_id'], $data['purchase_date'], $data['invoice_no']);
            if (Purchase::where('historical_invoice_key', $invoiceKey)->exists()) {
                throw ValidationException::withMessages(['invoice_no' => 'This historical supplier invoice is already recorded.']);
            }
            $lines = $data['lines'];
            $subtotal = round(array_sum(array_map(
                fn ($line) => (int) $line['qty'] * (float) $line['unit_cost'], $lines
            )), 2);
            $total = round($subtotal - (float) ($data['discount'] ?? 0) + (float) ($data['shipping_cost'] ?? 0), 2);
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);
            if ($total <= 0 || $paid > $total) {
                throw ValidationException::withMessages(['paid_amount' => 'Total must be positive and paid amount cannot exceed it.']);
            }

            $expense = null;
            $treatment = $data['accounting_treatment'];
            if ($treatment === 'reclassify_expense') {
                $expense = Expense::query()->includedInAccounting()->lockForUpdate()->find($data['expense_id'] ?? null);
                if (! $expense || $expense->reclassified_at || $expense->reclassified_purchase_id ||
                    Purchase::where('reclassified_expense_id', $expense->id)->exists()) {
                    throw ValidationException::withMessages(['expense_id' => 'Select an active expense that has not been reclassified.']);
                }
                if (abs((float) $expense->amount - $total) > 0.009 || abs($paid - $total) > 0.009) {
                    throw ValidationException::withMessages(['expense_id' => 'Expense amount, purchase total, and paid amount must match exactly. Review the invoice before saving.']);
                }
                $fund = FundTransaction::query()->includedInAccounting()->where('direction', 'out')
                    ->where('source', 'expense')->where('source_id', $expense->id)->lockForUpdate()->get();
                if ($fund->count() !== 1 || abs((float) $fund->first()->amount - $paid) > 0.009) {
                    throw ValidationException::withMessages(['expense_id' => 'Expense fund entry is missing, duplicated, or does not match. Reconcile it before reclassification.']);
                }
            } elseif ($treatment === 'already_recorded' && $paid > 0 && empty($data['accounting_reference'])) {
                throw ValidationException::withMessages(['accounting_reference' => 'Provide the existing payment reference for audit.']);
            } elseif ($treatment === 'new_payment' && $paid > AccountingSummaryService::lockedFundBalance()) {
                throw ValidationException::withMessages(['paid_amount' => 'Not enough balance in fund for a new payment.']);
            }

            $supplier = Supplier::query()->lockForUpdate()->findOrFail($data['supplier_id']);
            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'invoice_no' => $data['invoice_no'],
                'historical_invoice_key' => $invoiceKey,
                'purchase_date' => $data['purchase_date'],
                'purchase_mode' => 'historical',
                'accounting_treatment' => $treatment,
                'reclassified_expense_id' => $expense?->id,
                'total_qty' => array_sum(array_column($lines, 'qty')),
                'subtotal' => $subtotal,
                'discount' => $data['discount'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'grand_total' => $total,
                'paid_amount' => $paid,
                'due_amount' => round($total - $paid, 2),
                'note' => $data['note'] ?? null,
                'status' => 'completed',
                'created_by' => $adminId,
            ]);

            foreach ($lines as $line) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'variant_price_id' => $line['variant_price_id'] ?? null,
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => round((int) $line['qty'] * (float) $line['unit_cost'], 2),
                ]);
            }

            $supplier->current_due = round((float) $supplier->current_due + $total - $paid, 2);
            $supplier->save();

            if ($paid > 0) {
                $fund = null;
                if ($expense) {
                    $fund = FundTransaction::query()->includedInAccounting()->where('direction', 'out')
                        ->where('source', 'expense')->where('source_id', $expense->id)->lockForUpdate()->firstOrFail();
                } elseif ($treatment === 'new_payment') {
                    $fund = FundTransaction::create([
                        'direction' => 'out', 'source' => 'supplier_payment', 'amount' => $paid,
                        'transaction_date' => $data['purchase_date'],
                        'note' => 'Historical purchase payment: '.$purchase->invoice_no,
                        'created_by' => $adminId,
                    ]);
                }

                $payment = SupplierPayment::create([
                    'supplier_id' => $supplier->id, 'purchase_id' => $purchase->id,
                    'amount' => $paid, 'payment_date' => $data['purchase_date'],
                    'method' => $fund ? 'fund' : 'external',
                    'note' => $expense ? 'Reclassified expense #'.$expense->id : ($data['accounting_reference'] ?? 'Historical payment'),
                    'fund_transaction_id' => $fund?->id, 'created_by' => $adminId,
                ]);
                if ($fund) {
                    $fund->update(['source' => 'supplier_payment', 'source_id' => $payment->id]);
                }
            }

            if ($expense) {
                $expense->update([
                    'reclassified_at' => now(), 'reclassified_purchase_id' => $purchase->id,
                    'excluded_from_accounting_at' => now(),
                    'accounting_exclusion_reason' => 'Reclassified to historical purchase #'.$purchase->id,
                ]);
            }

            $balance = AccountingSummaryService::fundBalance();
            PurchaseLog::create([
                'purchase_id' => $purchase->id, 'action' => 'historical_create',
                'new_invoice_no' => $purchase->invoice_no, 'new_purchase_date' => $purchase->purchase_date,
                'new_paid_amount' => $paid, 'new_grand_total' => $total, 'new_note' => $purchase->note,
                'fund_balance_before' => $expense || $treatment === 'already_recorded' ? $balance : $balance + $paid,
                'fund_balance_after' => $balance,
                'description' => 'Historical purchase entered; no current stock movement. Treatment: '.$treatment.
                    ($expense ? '; expense #'.$expense->id.' reclassified' : '').
                    '; payment reference: '.($data['accounting_reference'] ?? 'n/a'),
                'performed_by' => $adminId,
            ]);

            return $purchase;
        });
    }

    private function invoiceKey(int $supplierId, string $date, string $invoice): string
    {
        return hash('sha256', $supplierId.'|'.$date.'|'.mb_strtolower(trim($invoice)));
    }
}
