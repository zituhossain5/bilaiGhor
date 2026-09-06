<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundTransaction;
use App\Models\Refund;
use App\Services\AccountingSummaryService;
use App\Services\InventoryService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['order', 'customer', 'processedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_invoice')) {
            $query->whereHas('order', function ($orderQuery) use ($request) {
                $orderQuery->where('invoice_id', 'like', '%'.$request->order_invoice.'%');
            });
        }

        $data = $query->latest()->paginate(15)->withQueryString();
        $statuses = ['pending', 'approved', 'rejected', 'processed'];
        $statusCounts = Refund::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('backEnd.refunds.index', compact('data', 'statuses', 'statusCounts'));
    }

    public function show($id)
    {
        $refund = Refund::with([
            'order.orderdetails.product.image',
            'order.orderdetails.image',
            'customer',
            'processedBy',
        ])->findOrFail($id);

        return view('backEnd.refunds.show', compact('refund'));
    }

    /** Approval is authorization only; cash leaves the fund when payment is processed. */
    public function approve(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending') {
            Toastr::error('This refund has already been reviewed.', 'Error');

            return back();
        }

        DB::transaction(function () use ($refund, $request) {
            $lockedRefund = Refund::query()->lockForUpdate()->findOrFail($refund->id);
            abort_unless($lockedRefund->status === 'pending', 422, 'This refund has already been reviewed.');

            $lockedRefund->update([
                'status' => 'approved',
                'admin_note' => $request->admin_note,
                'processed_by' => Auth::id(),
            ]);
        });

        Toastr::success('Refund approved. The fund will be deducted when payment is processed.', 'Success');

        return back();
    }

    public function reject(Request $request, $id)
    {
        $refund = Refund::with('order')->findOrFail($id);

        if (! in_array($refund->status, ['pending', 'approved'], true)) {
            Toastr::error('This refund has already been processed.', 'Error');

            return back();
        }

        DB::transaction(function () use ($refund, $request) {
            $lockedRefund = Refund::query()->with('order')->lockForUpdate()->findOrFail($refund->id);

            // Compatibility for old records that deducted funds during approval.
            $legacyDeduction = FundTransaction::includedInAccounting()
                ->where('source', 'refund')
                ->where('source_id', $lockedRefund->id)
                ->where('direction', 'out')
                ->first();

            if ($legacyDeduction) {
                $existingReversal = FundTransaction::includedInAccounting()
                    ->where('direction', 'in')
                    ->where('source', 'refund_reversal')
                    ->where('source_id', $lockedRefund->id)
                    ->exists();

                if (! $existingReversal) {
                    FundTransaction::create([
                        'direction' => 'in',
                        'source' => 'refund_reversal',
                        'source_id' => $lockedRefund->id,
                        'amount' => $legacyDeduction->amount,
                        'note' => 'Rejected refund reversal for Order #'.optional($lockedRefund->order)->invoice_id,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $lockedRefund->update([
                'status' => 'rejected',
                'admin_note' => $request->admin_note,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);
        });

        Toastr::success('Refund request rejected.', 'Success');

        return back();
    }

    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string|max:255',
            'refund_method' => 'required|in:original_payment,bkash,nagad,bank,manual',
            'refund_account' => 'required|string|max:255',
            'refund_account_name' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($id, $validated) {
            $refund = Refund::query()->with('order')->lockForUpdate()->findOrFail($id);

            if ($refund->status !== 'approved') {
                throw ValidationException::withMessages([
                    'transaction_id' => 'Only an approved refund can be processed.',
                ]);
            }

            $refundAmount = round((float) $refund->amount + (float) $refund->shipping_charge, 2);
            $balance = AccountingSummaryService::lockedFundBalance();
            if ($refundAmount > $balance) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'Insufficient fund balance. Available: '.number_format($balance, 2),
                ]);
            }

            $existingDeduction = FundTransaction::includedInAccounting()
                ->where('direction', 'out')
                ->where('source', 'refund')
                ->where('source_id', $refund->id)
                ->exists();

            if (! $existingDeduction) {
                FundTransaction::create([
                    'direction' => 'out',
                    'source' => 'refund',
                    'source_id' => $refund->id,
                    'amount' => $refundAmount,
                    'note' => 'Refund processed for Order #'.$refund->order->invoice_id.' - Refund ID: '.$refund->refund_id,
                    'created_by' => Auth::id(),
                ]);
            }

            $refund->update([
                'status' => 'processed',
                'transaction_id' => $validated['transaction_id'],
                'refund_method' => $validated['refund_method'],
                'refund_account' => $validated['refund_account'],
                'refund_account_name' => $validated['refund_account_name'] ?? null,
                'processed_at' => now(),
            ]);

            if ((int) $refund->order->order_status === 11) {
                InventoryService::releaseReservation($refund->order);
            }
        });

        Toastr::success('Refund processed and deducted from the fund exactly once.', 'Success');

        return back();
    }

    public function destroy($id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending') {
            Toastr::error('Only pending refunds can be deleted.', 'Error');

            return back();
        }

        $refund->delete();
        Toastr::success('Refund request deleted successfully.', 'Success');

        return back();
    }
}
