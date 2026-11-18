<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\FundTransaction;
use App\Support\OrderPurpose;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class OrderPurposeController extends Controller
{
    public function update(Request $request, Order $order)
    {
        $admin = $request->user('admin');
        abort_unless(OrderPurpose::canClassify($admin), 403);

        $data = $request->validate([
            'order_purpose' => ['required', Rule::in(array_keys(OrderPurpose::LABELS))],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($order, $admin, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $old = $locked->order_purpose ?: OrderPurpose::SALE;
            $new = $data['order_purpose'];
            if ($old === $new) {
                return;
            }
            if ($new !== OrderPurpose::SALE && (float) $locked->amount !== 0.0) {
                throw ValidationException::withMessages([
                    'order_purpose' => 'A non-sale purpose requires a zero-total order. Review the order amount and payment first.',
                ]);
            }
            if ($new !== OrderPurpose::SALE && (
                (float) ($locked->paid_amount ?? 0) > 0 ||
                DB::table('payments')->where('order_id', $locked->id)->where('amount', '>', 0)->exists() ||
                FundTransaction::query()->includedInAccounting()->where('source', 'sale')
                    ->where('source_id', $locked->id)->where('amount', '>', 0)->exists()
            )) {
                throw ValidationException::withMessages([
                    'order_purpose' => 'This order has a positive payment or sale credit. Reconcile it before classifying it as non-sale.',
                ]);
            }
            // Query builder deliberately avoids the Order model's status/payment/stock hooks.
            // The order's updated_at is also preserved as historical business context.
            DB::table('orders')->where('id', $locked->id)->update(['order_purpose' => $new]);
            DB::table('order_purpose_changes')->insert([
                'order_id' => $locked->id,
                'old_purpose' => $old,
                'new_purpose' => $new,
                'changed_by' => $admin->id,
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Order purpose saved. Inventory, payment and fund records were not changed.');
    }
}
