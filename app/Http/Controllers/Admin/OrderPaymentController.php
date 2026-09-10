<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_status' => ['required', 'string', 'in:' . implode(',', OrderPaymentService::adminStatuses())],
        ]);

        $status = strtolower(trim($validated['payment_status']));

        $result = OrderPaymentService::updateFromAdminStatus(
            Order::findOrFail($validated['order_id']),
            $status
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment status updated successfully.',
            'payment_status' => $result['status'],
            'status_key' => $result['status'],
            'paid_amount' => $result['paid'],
            'due_amount' => $result['due'],
        ]);
    }
}
