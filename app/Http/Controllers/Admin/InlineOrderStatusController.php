<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InlineOrderStatusController extends Controller
{
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'order_status' => ['required', 'integer', Rule::exists('order_statuses', 'id')],
        ]);

        $targetStatus = (int) $validated['order_status'];

        $updatedOrder = DB::transaction(function () use ($order, $targetStatus) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ((int) $lockedOrder->order_status === $targetStatus) {
                return $lockedOrder;
            }

            // This is the same idempotent transition used by the model hook. Running
            // it here makes an inventory error roll back the status request as a unit.
            InventoryService::syncOrderStatus($lockedOrder, $targetStatus);

            $lockedOrder->forceFill([
                'order_status' => $targetStatus,
                'updated_by' => Auth::guard('admin')->id(),
            ])->save();

            return $lockedOrder;
        });

        $status = OrderStatus::query()->findOrFail($updatedOrder->order_status);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully.',
            'order_status' => (int) $status->id,
            'status_label' => $status->name,
            'status_key' => str($status->name)->slug()->toString(),
        ]);
    }
}
